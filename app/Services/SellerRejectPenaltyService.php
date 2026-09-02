<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

/**
 * Penalty charged to a seller when they reject a paid order portion.
 */
class SellerRejectPenaltyService
{
    public const TYPE = 'order_reject_penalty';

    public const CATEGORY_CUSTOMER_SELLER = 'customer_seller';

    public const CATEGORY_PRIVATE_SELLER = 'private_seller';

    public const CATEGORY_BUSINESS_SELLER = 'business_seller';

    public static function sellerCategory(User $user): string
    {
        if ($user->role === 'customer') {
            return self::CATEGORY_CUSTOMER_SELLER;
        }

        $user->loadMissing('seller.store');

        if ($user->is_private_seller || ($user->role === 'seller' && ! $user->seller?->store)) {
            return self::CATEGORY_PRIVATE_SELLER;
        }

        return self::CATEGORY_BUSINESS_SELLER;
    }

    public static function penaltyAmount(User $user): float
    {
        $category = self::sellerCategory($user);
        $key = $category === self::CATEGORY_CUSTOMER_SELLER
            ? 'order_reject_penalty_customer_seller'
            : 'order_reject_penalty_private_seller';

        return max(0, round((float) Setting::get($key, '0'), 2));
    }

    /**
     * Debit seller wallet for rejecting an order. Allows negative balance. Idempotent per order.
     */
    public static function apply(Order $order, User $seller, string $reason): ?WalletTransaction
    {
        $amount = self::penaltyAmount($seller);
        if ($amount <= 0) {
            return null;
        }

        $wallet = Wallet::getOrCreateForUser($seller->id, 'PKR');

        $exists = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', self::TYPE)
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();
        if ($exists) {
            return null;
        }

        $wallet->refresh();
        $newBalance = round((float) $wallet->balance - $amount, 2);
        $wallet->update(['balance' => $newBalance]);

        $category = self::sellerCategory($seller);

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => self::TYPE,
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'description' => 'Order Reject Penalty — #' . ($order->order_number ?? $order->id),
            'meta' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
                'seller_category' => $category,
                'penalty_amount' => $amount,
            ],
        ]);
    }
}
