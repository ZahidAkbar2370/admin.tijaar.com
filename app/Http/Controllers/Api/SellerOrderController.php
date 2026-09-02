<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTimeline;
use App\Models\ProductVariant;
use App\Services\OrderWorkflowService;
use App\Services\WachatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'nullable|string|in:pending,paid,processing,approved,shipped,delivered,completed,cancelled,refunded,cancellation_requested',
            'order_number' => 'nullable|string|max:64',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $seller = $user->seller;
        $store = $seller?->store;

        $baseQuery = Order::query()
            ->whereHas('items', function ($q) use ($user, $store) {
                if ($store) {
                    $q->where('store_id', $store->id);
                } else {
                    $q->where('seller_id', $user->id)->whereNull('store_id');
                }
            });
        OrderWorkflowService::applySellerVisibleScope($baseQuery);

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->status);
        }
        if ($request->filled('order_number')) {
            $baseQuery->where('order_number', 'like', '%' . $request->order_number . '%');
        }
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $orderIds = (clone $baseQuery)->pluck('id');

        $orders = Order::whereIn('id', $orderIds)
            ->with([
                'user',
                'items' => fn ($q) => $q->where('store_id', $store?->id)->orWhere('seller_id', $user->id),
                'shipments',
                'shippingAddress',
                'coupon',
            ])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        $storeId = $store?->id;
        $sellerId = $user->id;
        $items = collect($orders->items())->map(function ($order) use ($storeId, $sellerId) {
            $sellerItems = $order->items->filter(function ($item) use ($storeId, $sellerId) {
                if ($storeId) {
                    return (int) $item->store_id === (int) $storeId;
                }
                return (int) $item->seller_id === (int) $sellerId;
            });
            $sellerSubtotal = $sellerItems->sum(fn ($i) => (float) $i->price * (int) $i->quantity);
            $order->seller_subtotal = round($sellerSubtotal, 2);
            $order->coupon_code = $order->coupon?->code;
            $order->discount_amount = $order->discount_amount;
            // Status for THIS seller's portion only (multi-seller safe)
            $sellerShipments = $storeId
                ? $order->shipments->where('store_id', $storeId)
                : $order->shipments->filter(fn ($s) => $s->store_id === null && (int) $s->seller_id === (int) $sellerId);
            $shipment = $sellerShipments->first();
            $portion = \App\Services\SellerFulfillmentService::portionStatus($sellerItems, $shipment);
            if (in_array($order->status, ['cancellation_requested'], true) && $portion === 'processing') {
                $order->seller_display_status = 'cancellation_requested';
            } else {
                $order->seller_display_status = $portion;
            }
            $order->seller_fulfillment_status = $portion;
            return $order;
        });

        return response()->json([
            'success' => true,
            'orders' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        $hasItem = $order->items()->where(function ($q) use ($store, $user) {
            if ($store) $q->where('store_id', $store->id);
            else $q->where('seller_id', $user->id);
        })->exists();

        if (!$hasItem) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $visible = Order::query()->where('id', $order->id);
        OrderWorkflowService::applySellerVisibleScope($visible);
        if (!$visible->exists()) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $order->load(['user', 'items.product.media', 'items.store', 'shippingAddress', 'timeline', 'shipments', 'coupon']);

        $storeId = $store?->id;
        $sellerId = $user->id;
        $sellerItems = $order->items->filter(function ($item) use ($storeId, $sellerId) {
            if ($storeId) {
                return (int) $item->store_id === (int) $storeId;
            }
            return (int) $item->seller_id === (int) $sellerId;
        });
        // Only show items with price > 0 (hide ghost/duplicate zero-value lines for variant-only orders)
        $sellerItems = $sellerItems->filter(fn ($i) => (float) $i->price > 0)->values();
        $order->seller_subtotal = round($sellerItems->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2);
        $order->seller_discount_allocated = round($sellerItems->sum(fn ($i) => (float) ($i->discount_allocated ?? 0)), 2);
        \App\Services\OrderFeeBreakdown::attachSellerView($order, $sellerItems);
        $order->coupon_code = $order->coupon?->code;

        // Return only this seller's items so they manage shipping/delivery for their own products only (Issue 8)
        $order->setRelation('items', $sellerItems->values());

        // Return only this seller's shipments (multi-seller: each seller sees their own)
        $sellerShipments = $storeId
            ? $order->shipments->where('store_id', $storeId)->values()
            : $order->shipments->filter(fn ($s) => empty($s->store_id) && (int) $s->seller_id === (int) $sellerId)->values();
        $order->setRelation('shipments', $sellerShipments);

        $sellerShipping = round((float) $sellerShipments->sum(fn ($s) => (float) ($s->shipping_cost ?? 0)), 2);
        if ($sellerShipping <= 0) {
            try {
                $breakdown = \App\Services\PkCourierShippingService::breakdownForOrder($order);
                $storeRow = collect($breakdown['stores'] ?? [])->first(function ($row) use ($storeId, $sellerId) {
                    if ($storeId) {
                        return (int) ($row['store_id'] ?? 0) === (int) $storeId;
                    }

                    return empty($row['store_id']) && (int) ($row['seller_id'] ?? 0) === (int) $sellerId;
                });
                $sellerShipping = round((float) ($storeRow['cost'] ?? 0), 2);
            } catch (\Throwable) {
                $sellerShipping = 0;
            }
        }
        if ($sellerShipping <= 0 && (float) ($order->shipping_cost ?? 0) > 0) {
            $distinctStores = $order->items()->distinct()->count('store_id');
            if ($distinctStores <= 1) {
                $sellerShipping = round((float) $order->shipping_cost, 2);
            }
        }
        $order->seller_shipping_cost = $sellerShipping;
        $order->seller_total = round(max(0, $order->seller_subtotal - $order->seller_discount_allocated + $sellerShipping), 2);
        // Net to seller (what they receive after all seller-side fees)
        $order->seller_you_receive = $order->seller_net;

        // Status for THIS seller's portion (fulfillment + shipment) — not whole-order status
        $shipment = $sellerShipments->first();
        $portion = \App\Services\SellerFulfillmentService::portionStatus($sellerItems, $shipment);
        if ($order->status === 'cancellation_requested' && in_array($portion, ['processing', 'approved'], true)) {
            $order->seller_display_status = 'cancellation_requested';
        } else {
            $order->seller_display_status = $portion;
        }
        $order->seller_fulfillment_status = $portion;
        $order->can_approve = $portion === 'processing' && ! in_array($order->status, ['cancelled', 'refunded'], true);
        $order->can_reject = in_array($portion, ['processing', 'approved'], true)
            && ! in_array($order->status, ['cancelled', 'refunded'], true);
        $order->can_add_tracking = $portion === 'approved'
            && ! $order->hasOpenReturnOrDispute();

        // Add image_url to each item (variant → snapshot → live product incl. soft-deleted)
        $variantIds = $order->items->map(fn ($i) => $i->options['variant_id'] ?? null)->filter()->unique()->values()->all();
        $variants = !empty($variantIds) ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();
        foreach ($order->items as $item) {
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            $variant = $variantId > 0 ? $variants->get($variantId) : null;
            $variantImagePath = $variant ? ($variant->image_path ?? (is_array($variant->image_paths ?? null) && !empty($variant->image_paths) ? $variant->image_paths[0] : null)) : null;
            $item->image_url = $item->resolveImageUrl($variantImagePath);
            $item->product_available = $item->isProductAvailable();
            if ($item->product && $item->product->trashed()) {
                $item->product->setAttribute('slug', null);
            }
        }

        foreach ($order->shipments as $shipment) {
            \App\Services\CourierShipmentPresenter::enrich($shipment);
            $hasCn = filled($shipment->tcs_cn_number)
                || filled($shipment->lcs_cn_number)
                || filled($shipment->tracking_number);
            // Sellers never see raw TCS/LCS API errors — only a soft pending flag + retry.
            $shipment->makeHidden(['tcs_raw_response', 'lcs_raw_response']);
            $shipment->setAttribute('courier_cn_pending', ! $hasCn);
            $shipment->setAttribute('lcs_booking_error', null);
            $shipment->setAttribute('courier_booking_error', null);
        }

        $order->makeHidden([
            'marketplace_fee',
            'online_transaction_fee',
            'platform_revenue',
            'marketplace_fee_type',
            'marketplace_fee_rate',
            'online_transaction_fee_type',
            'online_transaction_fee_rate',
            'seller_commission_total',
            'seller_marketplace_fee_total',
            'seller_online_transaction_fee_total',
            'seller_marketplace_fee_type',
            'seller_marketplace_fee_rate',
            'seller_online_transaction_fee_type',
            'seller_online_transaction_fee_rate',
            'seller_commission_type',
            'seller_commission_rate',
        ]);
        foreach ($order->items as $item) {
            $item->makeHidden(['commission_amount', 'marketplace_fee_allocated', 'online_transaction_fee_allocated']);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Legacy courier auto-booking endpoint. Tijaar no longer books shipments —
     * the seller hands the parcel over and enters the tracking number.
     */
    public function retryCourier(Request $request, Order $order)
    {
        return response()->json([
            'success' => false,
            'message' => \App\Services\CourierBookingService::MANUAL_TRACKING_MESSAGE,
            'tracking_ready' => false,
        ], 410);
    }

    public function approve(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (!$this->sellerOwnsOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $result = \App\Services\SellerFulfillmentService::approve($order, $user);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to approve',
            ], (int) ($result['code'] ?? 422));
        }

        $order->refresh();
        $items = \App\Services\SellerFulfillmentService::sellerItems($order, $user);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'seller_approved_at' => $order->seller_approved_at,
                'seller_display_status' => $result['seller_status'] ?? 'approved',
                'seller_fulfillment_status' => $result['seller_status'] ?? 'approved',
            ],
            'seller_item_ids' => $items->pluck('id')->values(),
        ]);
    }

    public function reject(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (!$this->sellerOwnsOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        $result = \App\Services\SellerFulfillmentService::reject(
            $order,
            $user,
            (string) $request->input('rejection_reason')
        );
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to reject',
            ], (int) ($result['code'] ?? 422));
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'refund_amount' => $result['refund_amount'] ?? 0,
            'penalty_amount' => $result['penalty_amount'] ?? 0,
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'seller_display_status' => $result['seller_status'] ?? 'rejected',
                'seller_fulfillment_status' => $result['seller_status'] ?? 'rejected',
            ],
        ]);
    }

    public function approveCancellation(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (!$this->sellerOwnsOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        if ($order->status !== 'cancellation_requested') {
            return response()->json([
                'success' => false,
                'message' => 'No cancellation request on this order.',
            ], 422);
        }

        $reason = $order->cancellation_reason ?: 'Cancellation approved by seller';
        OrderWorkflowService::createAndProcessRefund($order->fresh(), $reason);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation approved. Refund processed where applicable.',
            'order' => ['id' => $order->id, 'status' => $order->fresh()->status],
        ]);
    }

    public function rejectCancellation(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (!$this->sellerOwnsOrder($user, $order)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        if ($order->status !== 'cancellation_requested') {
            return response()->json([
                'success' => false,
                'message' => 'No cancellation request on this order.',
            ], 422);
        }

        $restoreStatus = $order->seller_approved_at ? 'approved' : 'processing';
        $order->update([
            'status' => $restoreStatus,
            'cancellation_requested_at' => null,
        ]);
        OrderTimeline::create([
            'order_id' => $order->id,
            'status' => $restoreStatus,
            'note' => 'Seller rejected cancellation request',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation request rejected.',
            'order' => ['id' => $order->id, 'status' => $order->status],
        ]);
    }

    private function sellerOwnsOrder($user, Order $order): bool
    {
        if ($user->role === 'seller') {
            $store = $user->seller?->store;
            return $order->items()->where(function ($q) use ($store, $user) {
                if ($store) {
                    $q->where('store_id', $store->id);
                } else {
                    $q->where('seller_id', $user->id);
                }
            })->exists();
        }

        // Private / customer-as-seller listings on this order
        if ($user->role === 'customer' || ($user->role === 'seller' && ($user->is_private_seller ?? false))) {
            return $order->items()
                ->where('seller_id', $user->id)
                ->exists();
        }

        return false;
    }
}
