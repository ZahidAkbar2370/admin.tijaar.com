<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin People segmentation (UI labels):
 * - Customer: buyers only (no C2C listings)
 * - Seller: customer accounts who also sell (private listings / sales)
 * - Private Seller (admin label): business sellers with stores (role=seller)
 */
class UserSegmentService
{
    /** All customer-role accounts (admin Customers menu). */
    public static function customersQuery(): Builder
    {
        return User::query()->where('role', 'customer');
    }

    /** Buyers only — role=customer with purchases, no private listings. */
    public static function buyersOnlyQuery(): Builder
    {
        return User::query()
            ->where('role', 'customer')
            ->whereHas('orders')
            ->whereDoesntHave('products', fn ($q) => $q->where('seller_type', 'private'));
    }

    /** Customer-as-seller — role=customer with purchases who also sell (private listings or sales). */
    public static function customerSellersQuery(): Builder
    {
        return User::query()
            ->where('role', 'customer')
            ->whereHas('orders')
            ->where(function ($q) {
                $q->whereHas('products', fn ($p) => $p->where('seller_type', 'private'))
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('order_items')
                            ->whereColumn('order_items.seller_id', 'users.id')
                            ->where('order_items.seller_type', 'private')
                            ->whereNotNull('order_items.seller_id');
                    });
            });
    }

    /** Business / store sellers (admin menu: Private Seller). */
    public static function businessSellersQuery(): Builder
    {
        return User::query()
            ->where('role', 'seller')
            ->whereHas('seller.store');
    }

    public static function applySearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }
        return $query->where(function ($qry) use ($search) {
            $qry->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    public static function applyStatus(Builder $query, ?string $status): Builder
    {
        if (!$status) {
            return $query;
        }
        if ($status === 'active') {
            return $query->where('is_banned', false)->where('is_suspended', false);
        }
        if ($status === 'suspended') {
            return $query->where('is_suspended', true);
        }
        if ($status === 'banned') {
            return $query->where('is_banned', true);
        }

        return $query;
    }

    public static function segmentLabel(User $user): string
    {
        if ($user->role === 'seller') {
            return 'Private Seller (Business)';
        }
        if ($user->products()->where('seller_type', 'private')->exists()) {
            return $user->is_private_seller ? 'Seller (Approved)' : 'Seller (Casual)';
        }

        return 'Customer';
    }
}
