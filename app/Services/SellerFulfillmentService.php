<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use App\Services\WachatService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Multi-seller fulfillment: each store/private seller approves, rejects, ships independently.
 * Order.status is an aggregate of non-rejected portions.
 */
class SellerFulfillmentService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Items belonging to this seller on the order (price > 0).
     */
    public static function sellerItems(Order $order, User $user): Collection
    {
        $order->loadMissing('items');
        $store = $user->role === 'seller' ? $user->seller?->store : null;

        return $order->items->filter(function (OrderItem $item) use ($user, $store) {
            if ((float) $item->price <= 0) {
                return false;
            }
            if ($store) {
                return (int) $item->store_id === (int) $store->id;
            }
            if ($user->role === 'customer') {
                return (int) $item->seller_id === (int) $user->id
                    && ($item->seller_type === 'private' || empty($item->store_id));
            }

            return (int) $item->seller_id === (int) $user->id && empty($item->store_id);
        })->values();
    }

    /**
     * Derive this seller's display / action status from their items (+ optional shipment).
     */
    public static function portionStatus(Collection $items, $shipment = null): string
    {
        if ($items->isEmpty()) {
            return self::STATUS_PENDING;
        }

        if ($items->every(fn ($i) => in_array($i->fulfillment_status, [self::STATUS_REJECTED, self::STATUS_CANCELLED], true))) {
            return self::STATUS_REJECTED;
        }

        $active = $items->filter(fn ($i) => ! in_array($i->fulfillment_status, [self::STATUS_REJECTED, self::STATUS_CANCELLED], true));
        if ($active->isEmpty()) {
            return self::STATUS_REJECTED;
        }

        if ($shipment) {
            if ($shipment->status === 'delivered') {
                return self::STATUS_DELIVERED;
            }
            if (in_array($shipment->status, ['shipped', 'in_transit'], true)) {
                return self::STATUS_SHIPPED;
            }
        }

        $statuses = $active->pluck('fulfillment_status')->unique()->values();
        if ($statuses->contains(self::STATUS_DELIVERED) && $statuses->count() === 1) {
            return self::STATUS_DELIVERED;
        }
        if ($statuses->contains(self::STATUS_SHIPPED)) {
            return self::STATUS_SHIPPED;
        }
        if ($statuses->every(fn ($s) => $s === self::STATUS_APPROVED)) {
            return self::STATUS_APPROVED;
        }
        if ($statuses->contains(self::STATUS_APPROVED)) {
            return self::STATUS_APPROVED;
        }
        if ($statuses->contains(self::STATUS_PROCESSING) || $statuses->contains(self::STATUS_PENDING)) {
            return self::STATUS_PROCESSING;
        }

        return (string) ($statuses->first() ?: self::STATUS_PROCESSING);
    }

    public static function markItemsProcessing(Order $order): void
    {
        $order->items()
            ->where(function ($q) {
                $q->whereNull('fulfillment_status')
                    ->orWhereIn('fulfillment_status', [self::STATUS_PENDING, '']);
            })
            ->update(['fulfillment_status' => self::STATUS_PROCESSING]);
    }

    /**
     * Approve this seller's portion only.
     */
    public static function approve(Order $order, User $user): array
    {
        $items = self::sellerItems($order, $user);
        if ($items->isEmpty()) {
            return ['ok' => false, 'message' => 'No items for your store on this order.', 'code' => 404];
        }

        $status = self::portionStatus($items);
        if ($status !== self::STATUS_PROCESSING) {
            return [
                'ok' => false,
                'message' => $status === self::STATUS_APPROVED
                    ? 'Your portion is already approved.'
                    : ($status === self::STATUS_REJECTED
                        ? 'Your portion was rejected.'
                        : 'Only your processing items can be approved. Current status: ' . $status . '.'),
                'code' => 422,
            ];
        }

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return ['ok' => false, 'message' => 'This order is closed.', 'code' => 422];
        }

        $now = now();
        OrderItem::whereIn('id', $items->pluck('id'))->update([
            'fulfillment_status' => self::STATUS_APPROVED,
            'approved_at' => $now,
        ]);

        $order->refresh();
        $order->load('items', 'shipments');

        if (! $order->seller_approved_at) {
            $order->update(['seller_approved_at' => $now]);
        }

        $label = self::sellerLabel($user);
        OrderTimeline::create([
            'order_id' => $order->id,
            'status' => 'approved',
            'note' => $label . ' approved their items',
        ]);

        self::syncOrderStatus($order->fresh(['items', 'shipments']));

        WachatService::notifyOrderApproved($order->fresh('user'));

        return [
            'ok' => true,
            'message' => 'Your items are approved. Ship the parcel, then add your tracking number.',
            'seller_status' => self::STATUS_APPROVED,
            'order_status' => $order->fresh()->status,
        ];
    }

    /**
     * Reject this seller's portion only; refund that portion; leave other sellers intact.
     */
    public static function reject(Order $order, User $user, string $reason): array
    {
        $items = self::sellerItems($order, $user);
        if ($items->isEmpty()) {
            return ['ok' => false, 'message' => 'No items for your store on this order.', 'code' => 404];
        }

        $status = self::portionStatus($items);
        if (! in_array($status, [self::STATUS_PROCESSING, self::STATUS_APPROVED], true)) {
            return [
                'ok' => false,
                'message' => 'Only your processing or approved items can be rejected. Current status: ' . $status . '.',
                'code' => 422,
            ];
        }

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return ['ok' => false, 'message' => 'This order is already closed.', 'code' => 422];
        }

        $refundAmount = self::refundableForItems($order, $items);
        $isPaid = in_array($order->payment_status, ['paid', 'partial_paid'], true);
        $buyerRefundAmount = $isPaid ? $refundAmount : 0.0;
        $penaltyAmount = SellerRejectPenaltyService::penaltyAmount($user);
        $label = self::sellerLabel($user);

        $refund = DB::transaction(function () use ($order, $items, $reason, $buyerRefundAmount, $label, $user, $penaltyAmount) {
            $now = now();
            foreach ($items as $item) {
                $lineRefund = self::lineRefundAmount($item);
                $item->update([
                    'fulfillment_status' => self::STATUS_REJECTED,
                    'rejected_at' => $now,
                    'rejection_reason' => $reason,
                    'refund_amount' => $lineRefund,
                ]);
            }

            // Allocate seller shipping into the first rejected line for display (sum matches total).
            $shippingPart = self::shippingForItems($order, $items);
            if ($shippingPart > 0 && $items->isNotEmpty()) {
                $first = $items->first()->fresh();
                $first->update([
                    'refund_amount' => round((float) ($first->refund_amount ?? 0) + $shippingPart, 2),
                ]);
            }

            self::restoreStockForItems($items);

            $refund = null;
            if ($buyerRefundAmount > 0) {
                $refund = OrderWorkflowService::createPartialRefundForAmount(
                    $order->fresh(),
                    $buyerRefundAmount,
                    'Seller rejected (' . $label . '): ' . $reason
                        . ' (item + shipping only; marketplace and online transaction fees are non-refundable)'
                );
            }

            $penaltyTxn = SellerRejectPenaltyService::apply($order->fresh(), $user, $reason);

            $timelineNote = $label . ' rejected their items';
            if ($buyerRefundAmount > 0) {
                $timelineNote .= ' — buyer refunded ' . number_format($buyerRefundAmount, 2)
                    . ' (item + shipping; fees non-refundable)';
            }
            if ($penaltyTxn) {
                $timelineNote .= ' — seller penalty ' . number_format(abs((float) $penaltyTxn->amount), 2) . ' PKR';
            }
            $timelineNote .= ': ' . $reason;

            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'rejected',
                'note' => $timelineNote,
            ]);

            self::syncOrderStatus($order->fresh(['items', 'shipments', 'refunds']));

            return $refund;
        });

        $message = 'Your items were rejected.';
        if ($buyerRefundAmount > 0) {
            $message .= ' Buyer refunded ' . number_format($buyerRefundAmount, 2)
                . ' (item + shipping only; marketplace and online fees are not refunded).';
        } else {
            $message .= ' Other sellers on this order are unaffected.';
        }
        if ($penaltyAmount > 0) {
            $message .= ' A penalty of ' . number_format($penaltyAmount, 2) . ' PKR was deducted from your wallet.';
        }

        return [
            'ok' => true,
            'message' => $message,
            'refund_amount' => $buyerRefundAmount,
            'penalty_amount' => $penaltyAmount,
            'refund_id' => $refund?->id,
            'seller_status' => self::STATUS_REJECTED,
            'order_status' => $order->fresh()->status,
        ];
    }

    public static function lineRefundAmount(OrderItem $item): float
    {
        $gross = (float) $item->price * (int) $item->quantity;
        $discount = (float) ($item->discount_allocated ?? 0);

        return max(0, round($gross - $discount, 2));
    }

    public static function shippingForItems(Order $order, Collection $items): float
    {
        if ($items->isEmpty()) {
            return 0.0;
        }

        $storeId = $items->first()->store_id;
        $sellerId = $items->first()->seller_id;

        try {
            $breakdown = PkCourierShippingService::breakdownForOrder($order);
            $row = collect($breakdown['stores'] ?? [])->first(function ($r) use ($storeId, $sellerId) {
                if ($storeId) {
                    return (int) ($r['store_id'] ?? 0) === (int) $storeId;
                }

                return empty($r['store_id']) && (int) ($r['seller_id'] ?? 0) === (int) $sellerId;
            });
            if ($row && (float) ($row['cost'] ?? 0) > 0) {
                return round((float) $row['cost'], 2);
            }
        } catch (\Throwable) {
            // fall through
        }

        $orderShipping = (float) ($order->shipping_cost ?? 0);
        if ($orderShipping <= 0) {
            return 0.0;
        }

        $order->loadMissing('items');
        $allPriced = $order->items->filter(fn ($i) => (float) $i->price > 0);
        $portions = $allPriced->groupBy(function ($i) {
            return $i->store_id ? 's:' . $i->store_id : 'u:' . $i->seller_id;
        });
        $n = max(1, $portions->count());

        return round($orderShipping / $n, 2);
    }

    public static function refundableForItems(Order $order, Collection $items): float
    {
        $goods = $items->sum(fn ($i) => self::lineRefundAmount($i));
        $shipping = self::shippingForItems($order, $items);

        return max(0, round($goods + $shipping, 2));
    }

    public static function restoreStockForItems(Collection $items): void
    {
        foreach ($items as $item) {
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            if ($variantId > 0) {
                ProductVariant::where('id', $variantId)->increment('quantity', $qty);
            } elseif ($item->product) {
                $item->product->increment('quantity', $qty);
            } elseif ($item->product_id) {
                \App\Models\Product::where('id', $item->product_id)->increment('quantity', $qty);
            }
        }
    }

    /**
     * Recompute order.status from per-item fulfillment (ignores fully rejected portions for shipping progress).
     */
    public static function syncOrderStatus(Order $order): void
    {
        $order->loadMissing('items', 'shipments');

        if ($order->status === 'cancellation_requested') {
            return;
        }

        $priced = $order->items->filter(fn ($i) => (float) $i->price > 0);
        if ($priced->isEmpty()) {
            return;
        }

        $active = $priced->filter(fn ($i) => ! in_array($i->fulfillment_status, [self::STATUS_REJECTED, self::STATUS_CANCELLED], true));

        // Every seller rejected → close order (full cancel/refund)
        if ($active->isEmpty()) {
            $hasRefund = $order->refunds()->where('status', 'completed')->exists()
                || $priced->sum(fn ($i) => (float) ($i->refund_amount ?? 0)) > 0;
            $updates = ['status' => $hasRefund ? 'refunded' : 'cancelled'];
            // Only mark payment fully refunded when nothing remains to fulfill
            if ($hasRefund) {
                $updates['payment_status'] = 'refunded';
            }
            $order->update($updates);

            return;
        }

        // Group active by seller portion
        $groups = $active->groupBy(fn ($i) => $i->store_id ? 's:' . $i->store_id : 'u:' . $i->seller_id);
        $anyProcessing = false;
        $anyApprovedOnly = false;
        $anyShipped = false;
        $allDelivered = true;

        foreach ($groups as $key => $groupItems) {
            $shipment = null;
            if (str_starts_with($key, 's:')) {
                $sid = (int) substr($key, 2);
                $shipment = $order->shipments->firstWhere('store_id', $sid);
            } else {
                $uid = (int) substr($key, 2);
                $shipment = $order->shipments->first(fn ($s) => (int) $s->seller_id === $uid && empty($s->store_id));
            }

            $ps = self::portionStatus($groupItems, $shipment);
            if (in_array($ps, [self::STATUS_PROCESSING, self::STATUS_PENDING], true)) {
                $anyProcessing = true;
                $allDelivered = false;
            } elseif ($ps === self::STATUS_APPROVED) {
                $anyApprovedOnly = true;
                $allDelivered = false;
            } elseif (in_array($ps, [self::STATUS_SHIPPED], true)) {
                $anyShipped = true;
                $allDelivered = false;
            } elseif ($ps === self::STATUS_DELIVERED) {
                // ok
            } else {
                $allDelivered = false;
            }
        }

        if ($allDelivered) {
            $new = 'completed';
        } elseif ($anyShipped) {
            $new = 'shipped';
        } elseif ($anyProcessing) {
            $new = 'processing';
        } elseif ($anyApprovedOnly) {
            $new = 'approved';
        } else {
            $new = 'processing';
        }

        // Do NOT change payment_status on partial seller rejects — payment stays paid/partial_paid.
        // Refunds are shown separately in the payment summary (refunded_total / remaining_total).
        $order->update(['status' => $new]);
    }

    public static function sellerLabel(User $user): string
    {
        if ($user->role === 'seller' && $user->seller?->store) {
            return 'Store "' . ($user->seller->store->name ?? 'Seller') . '"';
        }

        return 'Seller "' . ($user->name ?? 'Seller') . '"';
    }

    /**
     * Buyer-facing groups: one card per store/seller with items, status, tracking, refund.
     */
    public static function buyerSellerGroups(Order $order): array
    {
        $order->loadMissing(['items.store', 'shipments', 'refunds']);
        $priced = $order->items->filter(fn ($i) => (float) $i->price > 0);

        $groups = $priced->groupBy(fn ($i) => $i->store_id ? 's:' . $i->store_id : 'u:' . $i->seller_id);

        return $groups->map(function (Collection $items, string $key) use ($order) {
            $first = $items->first();
            $storeId = $first->store_id ? (int) $first->store_id : null;
            $sellerId = (int) $first->seller_id;
            $shipment = $storeId
                ? $order->shipments->firstWhere('store_id', $storeId)
                : $order->shipments->first(fn ($s) => (int) $s->seller_id === $sellerId && empty($s->store_id));

            $status = self::portionStatus($items, $shipment);
            $refundTotal = round($items->sum(fn ($i) => (float) ($i->refund_amount ?? 0)), 2);
            $name = $first->store?->name
                ?: ($first->seller_type === 'private' ? 'Private seller' : 'Seller');

            if ($shipment) {
                CourierShipmentPresenter::enrich($shipment);
            }

            return [
                'key' => $key,
                'store_id' => $storeId,
                'seller_id' => $sellerId,
                'seller_type' => $first->seller_type,
                'name' => $name,
                'status' => $status,
                'rejection_reason' => $items->pluck('rejection_reason')->filter()->first(),
                'refund_amount' => $refundTotal > 0 ? $refundTotal : null,
                'item_ids' => $items->pluck('id')->values()->all(),
                'product_names' => $items->pluck('product_name')->unique()->values()->all(),
                'items_subtotal' => round($items->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2),
                'shipment' => $shipment ? [
                    'id' => $shipment->id,
                    'carrier' => $shipment->carrier,
                    'tracking_number' => $shipment->tracking_number,
                    'tracking_url' => $shipment->tracking_url ?? $shipment->getAttribute('tracking_url'),
                    'status' => $shipment->status,
                    'courier_cn' => CourierShipmentPresenter::cnNumber($shipment),
                ] : null,
            ];
        })->values()->all();
    }
}
