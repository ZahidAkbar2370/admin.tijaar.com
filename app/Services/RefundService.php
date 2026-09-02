<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Process a refund (admin): gateway refund or refund to wallet.
     */
    public static function processRefund(
        Refund $refund,
        string $refundType = 'gateway',
        ?string $gatewayRefundId = null,
        ?int $processedBy = null
    ): void {
        if ($refund->status !== 'pending') {
            throw new \InvalidArgumentException('Refund is not pending.');
        }

        DB::transaction(function () use ($refund, $refundType, $gatewayRefundId, $processedBy) {
            if ($refundType === 'wallet') {
                $order = $refund->order;
                $wallet = Wallet::getOrCreateForUser($order->user_id, $order->market === 'AE' ? 'AED' : 'PKR');
                $newBalance = (float) $wallet->balance + (float) $refund->amount;
                $wallet->update(['balance' => $newBalance]);
                $reasonText = is_string($refund->reason) ? trim($refund->reason) : '';
                $wt = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'refund',
                    'amount' => $refund->amount,
                    'balance_after' => $newBalance,
                    'reference_type' => 'refund',
                    'reference_id' => $refund->id,
                    'description' => 'Order Refunded — ' . $order->order_number
                        . ($reasonText !== '' ? ' — ' . \Illuminate\Support\Str::limit($reasonText, 120) : ''),
                    'meta' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'refund_id' => $refund->id,
                    ],
                ]);
                $refund->update([
                    'status' => 'completed',
                    'refund_type' => 'wallet',
                    'wallet_transaction_id' => $wt->id,
                    'processed_by' => $processedBy,
                    'processed_at' => now(),
                ]);
            } else {
                $refund->update([
                    'status' => 'completed',
                    'refund_type' => 'gateway',
                    'gateway_refund_id' => $gatewayRefundId,
                    'processed_by' => $processedBy,
                    'processed_at' => now(),
                ]);
            }
            $payment = $refund->payment;
            $totalRefunded = $payment->refunds()->where('status', 'completed')->sum('amount');
            if ($totalRefunded >= (float) $payment->amount) {
                $payment->update(['status' => 'refunded']);
            } else {
                $payment->update(['status' => 'partial_refunded']);
            }
        });
    }

    /**
     * Create partial refund request (customer or admin).
     */
    public static function createPartialRefund(Payment $payment, float $amount, ?string $reason = null, ?int $requestedBy = null): Refund
    {
        $alreadyRefunded = $payment->refunds()->whereIn('status', ['pending', 'completed'])->sum('amount');
        $available = max(0, (float) $payment->amount - $alreadyRefunded);
        if ($amount > $available || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid refund amount. Available: ' . $available);
        }
        return Refund::create([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
}
