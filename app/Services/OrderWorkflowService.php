<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTimeline;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\WachatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderWorkflowService
{
    /**
     * Mark online/wallet payment success: payment_status paid (or partial_paid), status processing.
     * Notifies sellers here (not at place-order for unpaid online).
     */
    public static function markPaymentSuccess(Order $order, string $note = 'Payment completed'): void
    {
        $codRemainder = (float) ($order->cod_amount ?? 0);
        $isPartial = $codRemainder > 0.009 || $order->payment_method === 'jazzcash_partial';

        $paymentStatus = $isPartial ? 'partial_paid' : 'paid';
        $timelineStatus = $isPartial ? 'partial_paid' : 'processing';
        $timelineNote = $isPartial
            ? ($note . '. COD balance: ' . number_format($codRemainder, 2) . ' PKR')
            : $note;

        $order->update([
            'payment_status' => $paymentStatus,
            'status' => 'processing',
        ]);

        OrderTimeline::create([
            'order_id' => $order->id,
            'status' => $timelineStatus,
            'note' => $timelineNote,
        ]);

        ActivityLogger::log([
            'action_type' => 'payment_success',
            'action_by' => $order->user_id,
            'target_table' => 'orders',
            'action_on' => $order->id,
            'description' => "Payment success for order {$order->order_number}: {$timelineNote}",
        ]);

        WachatService::notifyPaymentSuccess($order->fresh('user'));

        SellerFulfillmentService::markItemsProcessing($order->fresh());

        self::notifySellers($order->fresh());

        WalletLedgerService::recordBuyerOrderPayment($order->fresh());
    }

    /**
     * Refundable amount = product subtotal - discount + shipping. Excludes marketplace/online fees and commission.
     */
    public static function refundableAmount(Order $order): float
    {
        $subtotal = (float) ($order->subtotal ?? 0);
        $discount = (float) ($order->discount_amount ?? 0);
        $shipping = (float) ($order->shipping_cost ?? 0);

        return max(0, round($subtotal - $discount + $shipping, 2));
    }

    /**
     * Partial refund for a specific amount (e.g. one seller's rejected portion).
     * Does NOT cancel the whole order — caller syncs status.
     */
    public static function createPartialRefundForAmount(Order $order, float $amount, string $reason): ?Refund
    {
        $amount = max(0, round($amount, 2));
        if ($amount <= 0) {
            return null;
        }

        $paidGateways = ['jazzcash', 'stripe', 'easypaisa', 'wallet', 'paypal'];
        $payment = $order->payments()
            ->whereIn('gateway', $paidGateways)
            ->whereIn('status', ['completed', 'partial_refunded'])
            ->orderByDesc('id')
            ->first();

        if (!$payment) {
            // COD / unpaid — no wallet credit; status sync handles cancelled/refunded labels
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'partial_cancelled',
                'note' => $reason . ' (no online payment to refund; amount ' . number_format($amount, 2) . ')',
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason) {
            $already = (float) $payment->refunds()->whereIn('status', ['pending', 'completed'])->sum('amount');
            $available = max(0, (float) $payment->amount - $already);
            $toRefund = min($amount, $available);
            if ($toRefund <= 0) {
                return null;
            }

            $refund = RefundService::createPartialRefund($payment, $toRefund, $reason);
            RefundService::processRefund($refund, 'wallet');

            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'partial_refund',
                'note' => $reason . ' Refunded ' . number_format($toRefund, 2) . ' to wallet.',
            ]);

            return $refund->fresh();
        });
    }

    /**
     * Create and process refund for the full remaining refundable amount. Updates order to refunded/cancelled.
     */
    public static function createAndProcessRefund(Order $order, string $reason): ?Refund
    {
        $amount = self::refundableAmount($order);
        // Subtract amounts already refunded for partial seller rejects
        $alreadyRefunded = (float) $order->refunds()->whereIn('status', ['pending', 'completed'])->sum('amount');
        $amount = max(0, round($amount - $alreadyRefunded, 2));

        if ($amount <= 0) {
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'note' => $reason,
            ]);
            self::restoreStock($order);

            return null;
        }

        $paidGateways = ['jazzcash', 'stripe', 'easypaisa', 'wallet', 'paypal'];
        $payment = $order->payments()
            ->whereIn('gateway', $paidGateways)
            ->whereIn('status', ['completed', 'partial_refunded'])
            ->orderByDesc('id')
            ->first();

        if (!$payment) {
            // COD / unpaid — cancel without gateway refund
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'payment_status' => $order->payment_status === 'paid' || $order->payment_status === 'partial_paid'
                    ? 'refunded'
                    : $order->payment_status,
            ]);
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'note' => $reason,
            ]);
            self::restoreStock($order);

            return null;
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason) {
            $refund = RefundService::createPartialRefund($payment, $amount, $reason);
            RefundService::processRefund($refund, 'wallet');

            $order->update([
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'cancellation_reason' => $reason,
            ]);
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'refunded',
                'note' => $reason . ' Refunded ' . number_format($amount, 2) . ' to wallet (item + shipping).',
            ]);
            self::restoreStock($order);

            return $refund->fresh();
        });
    }

    public static function restoreStock(Order $order): void
    {
        $order->loadMissing('items.product');
        // Skip items already rejected/refunded at seller-portion level
        $itemsToRestore = $order->items->filter(function ($i) {
            if ((float) $i->price <= 0) {
                return false;
            }
            return ! in_array($i->fulfillment_status ?? '', ['rejected', 'cancelled'], true);
        });
        foreach ($itemsToRestore as $item) {
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            if ($variantId > 0) {
                ProductVariant::where('id', $variantId)->increment('quantity', $qty);
            } elseif ($item->product) {
                $item->product->increment('quantity', $qty);
            }
        }
    }

    public static function notifySellers(Order $order): void
    {
        try {
            $order->loadMissing('items.store.seller');
            $sellerUserIds = [];
            foreach ($order->items as $item) {
                if ($item->store_id && $item->store?->seller?->user_id) {
                    $sellerUserIds[(int) $item->store->seller->user_id] = true;
                } elseif ($item->seller_id) {
                    $sellerUserIds[(int) $item->seller_id] = true;
                }
            }
            foreach (array_keys($sellerUserIds) as $userId) {
                $sellerUser = User::find($userId);
                $email = is_string($sellerUser?->email) ? trim($sellerUser->email) : '';
                if ($sellerUser && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $sellerUser->notify(new \App\Notifications\OrderPlacedSellerNotification($order));
                }
            }

            WachatService::notifyOrderPlacedSellers($order);
        } catch (\Throwable $e) {
            Log::warning('Order seller notification failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Seller-visible orders: paid/partial_paid OR COD (including pending COD), not unpaid online pending.
     */
    public static function applySellerVisibleScope($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->where('payment_method', 'cod')
                    ->whereIn('status', [
                        'pending', 'processing', 'approved', 'shipped', 'delivered', 'completed',
                        'cancellation_requested', 'cancelled', 'refunded',
                    ]);
            })->orWhere(function ($inner) {
                $inner->whereIn('payment_status', ['paid', 'partial_paid'])
                    ->whereIn('status', [
                        'processing', 'approved', 'shipped', 'delivered', 'completed',
                        'cancellation_requested', 'cancelled', 'refunded', 'paid',
                    ]);
            });
        });
    }
}
