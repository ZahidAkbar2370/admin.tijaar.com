<?php

namespace App\Support;

/**
 * Human-readable wallet ledger titles for customer + private seller UIs.
 *
 * Examples:
 * - Payment Added to Wallet +10
 * - Order Payment -10
 * - Order Refunded +8
 * - Payment for Product Promotion -10
 */
class WalletTransactionLabel
{
    public static function title(string $type, float|string|null $amount = 0, ?array $meta = null): string
    {
        $t = strtolower(trim($type));
        $amt = (float) $amount;
        $meta = is_array($meta) ? $meta : [];

        if (($meta['purpose'] ?? null) === 'admin_adjustment') {
            return $amt >= 0 ? 'Payment Added to Wallet' : 'Wallet Adjustment';
        }

        return match ($t) {
            'deposit', 'credit' => 'Payment Added to Wallet',
            'refund' => 'Order Refunded',
            'package_purchase' => 'Payment for Product Promotion',
            'listing_fee' => 'Payment for Listing Fee',
            'order_reject_penalty' => 'Order Reject Penalty',
            'earnings_credit' => 'Earnings Added to Wallet',
            'payout' => 'Payout Requested',
            'payout_refund' => 'Payout Returned to Wallet',
            'debit' => 'Wallet Payment',
            'order_payment' => $amt > 0 ? 'Order Earnings' : 'Order Payment',
            default => $t !== ''
                ? ucwords(str_replace(['_', '-'], ' ', $t))
                : 'Transaction',
        };
    }

    /** Signed amount string for display, e.g. "+10.00" / "-10.00". */
    public static function signedAmount(float|string|null $amount, int $decimals = 2): string
    {
        $amt = round((float) $amount, $decimals);
        $abs = number_format(abs($amt), $decimals, '.', '');

        return ($amt >= 0 ? '+' : '-') . $abs;
    }

    /** Combined line: "Payment Added to Wallet +10.00" */
    public static function displayLine(string $type, float|string|null $amount, ?array $meta = null, int $decimals = 2): string
    {
        return self::title($type, $amount, $meta) . ' ' . self::signedAmount($amount, $decimals);
    }
}
