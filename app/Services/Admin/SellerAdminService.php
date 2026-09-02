<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PayoutService;
use App\Support\RegistrationSource;
use Illuminate\Http\RedirectResponse;

class SellerAdminService
{
    /** @return array<string, mixed> */
    public static function overviewStats(User $user): array
    {
        $user->load(['seller.store']);
        $user->loadCount([
            'products as products_count' => fn ($q) => $q->where('seller_type', 'business'),
        ]);

        $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');
        $storeId = $user->seller?->store?->id;

        $totalEarningsSum = 0.0;
        if ($storeId) {
            $totalEarningsSum = (float) OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.store_id', $storeId)
                ->whereIn('orders.status', PayoutService::EARNINGS_ORDER_STATUSES)
                ->where('orders.payment_status', '!=', 'refunded')
                ->selectRaw('COALESCE(SUM((order_items.quantity * order_items.price) - COALESCE(order_items.discount_allocated, 0) - COALESCE(order_items.commission_amount, 0) - COALESCE(order_items.marketplace_fee_allocated, 0) - COALESCE(order_items.online_transaction_fee_allocated, 0)), 0) as net_total')
                ->value('net_total');
        }

        return [
            'wallet' => $wallet,
            'productsCount' => (int) ($user->products_count ?? 0),
            'totalEarningsSum' => $totalEarningsSum,
            'availableEarnings' => (float) (PayoutService::getEarningsForUser($user, 'business')['net'] ?? 0),
            'store' => $user->seller?->store,
            'seller' => $user->seller,
        ];
    }

    public static function ensureSeller(User $user): ?RedirectResponse
    {
        if ($user->role !== 'seller') {
            return redirect()->route('admin.sellers.index')->with('error', 'Not a private seller.');
        }

        return null;
    }

    /** @return list<array{label: string, route: string, active: string}> */
    public static function navItems(): array
    {
        return [
            ['label' => 'Overview', 'route' => 'admin.sellers.show', 'active' => 'admin.sellers.show'],
            ['label' => 'Edit Profile', 'route' => 'admin.sellers.profile', 'active' => 'admin.sellers.profile'],
            ['label' => 'KYC & Bank', 'route' => 'admin.sellers.kyc', 'active' => 'admin.sellers.kyc'],
            ['label' => 'Store', 'route' => 'admin.sellers.storefront', 'active' => 'admin.sellers.storefront'],
            ['label' => 'Address', 'route' => 'admin.sellers.addresses', 'active' => 'admin.sellers.addresses'],
            ['label' => 'Alerts Preferences', 'route' => 'admin.sellers.alerts', 'active' => 'admin.sellers.alerts'],
            ['label' => 'Update Wallet', 'route' => 'admin.sellers.wallet', 'active' => 'admin.sellers.wallet'],
            ['label' => 'Purchase Promotion', 'route' => 'admin.sellers.promotions', 'active' => 'admin.sellers.promotions'],
            ['label' => 'Products', 'route' => 'admin.sellers.products.index', 'active' => 'admin.sellers.products.*'],
            ['label' => 'Transactions', 'route' => 'admin.sellers.transactions', 'active' => 'admin.sellers.transactions'],
            ['label' => 'Orders', 'route' => 'admin.sellers.orders', 'active' => 'admin.sellers.orders'],
            ['label' => 'Account Actions', 'route' => 'admin.sellers.account-actions', 'active' => 'admin.sellers.account-actions'],
        ];
    }

    public static function sourceLabel(User $user): string
    {
        return RegistrationSource::label($user->registration_source);
    }
}
