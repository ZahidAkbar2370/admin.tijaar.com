<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;

/**
 * Immutable wallet ledger entries for buyer/seller activity.
 */
class WalletLedgerService
{
    /**
     * Record a buyer order payment when the order becomes paid (wallet or online gateway).
     * Does not modify wallet balance — checkout wallet debit is handled at place-order.
     */
    public static function recordBuyerOrderPayment(Order $order, ?Payment $payment = null): ?WalletTransaction
    {
        if (! in_array($order->payment_status, ['paid', 'partial_paid'], true)) {
            return null;
        }

        $order->loadMissing('user');
        $currency = ($order->market ?? '') === 'AE' ? 'AED' : 'PKR';
        $wallet = Wallet::getOrCreateForUser((int) $order->user_id, $currency);

        if (self::buyerOrderPaymentExists($wallet->id, (int) $order->id)) {
            return null;
        }

        $payment = $payment ?? $order->payments()
            ->whereIn('gateway', ['jazzcash', 'stripe', 'easypaisa', 'wallet', 'paypal'])
            ->whereIn('status', ['completed', 'partial_refunded'])
            ->orderByDesc('id')
            ->first();

        if (! $payment) {
            return null;
        }

        $payAmount = round((float) $payment->amount, 2);
        if ($payAmount <= 0) {
            return null;
        }

        $gateway = (string) $payment->gateway;
        $wallet->refresh();
        $balanceAfter = round((float) $wallet->balance, 2);

        $gatewayLabel = match (strtolower($gateway)) {
            'jazzcash', 'jazzcash_partial' => 'JazzCash',
            'easypaisa' => 'Easypaisa',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'wallet' => 'Wallet',
            default => ucwords(str_replace('_', ' ', $gateway)),
        };

        $description = $gateway === 'wallet'
            ? 'Order Payment — ' . $order->order_number
            : 'Order Payment — #' . $order->order_number . ' (' . $gatewayLabel . ')';

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'order_payment',
            'amount' => -$payAmount,
            'balance_after' => $balanceAfter,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'description' => $description,
            'meta' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'affects_balance' => $gateway === 'wallet',
            ],
        ]);
    }

    /**
     * Credit wallet for a completed deposit (idempotent per deposit id).
     */
    public static function recordDeposit(
        Wallet $wallet,
        float $amount,
        int $depositId,
        string $description,
        array $meta = []
    ): ?WalletTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $exists = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'deposit')
            ->where('reference_type', 'wallet_deposit')
            ->where('reference_id', $depositId)
            ->exists();
        if ($exists) {
            return null;
        }

        $wallet->refresh();
        $newBalance = round((float) $wallet->balance + $amount, 2);
        $wallet->update(['balance' => $newBalance]);

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_type' => 'wallet_deposit',
            'reference_id' => $depositId,
            'description' => $description,
            'meta' => $meta,
        ]);
    }

    private static function buyerOrderPaymentExists(int $walletId, int $orderId): bool
    {
        return WalletTransaction::query()
            ->where('wallet_id', $walletId)
            ->where('type', 'order_payment')
            ->where('reference_type', 'order')
            ->where('reference_id', $orderId)
            ->where('amount', '<', 0)
            ->exists();
    }
}
