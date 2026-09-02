<?php

namespace App\Services\Admin;

use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;

class CustomerAdminService
{
    /** @return array<string, mixed> */
    public static function overviewStats(User $user): array
    {
        $user->loadCount([
            'orders as orders_count',
            'products as private_listings_count' => fn ($q) => $q->where('seller_type', 'private'),
        ]);

        $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');

        $totalPurchasesSum = (float) Order::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('payment_status', '!=', 'refunded')
            ->sum('total');

        $totalEarningsSum = (float) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.seller_id', $user->id)
            ->where('order_items.seller_type', 'private')
            ->whereIn('orders.status', PayoutService::EARNINGS_ORDER_STATUSES)
            ->where('orders.payment_status', '!=', 'refunded')
            ->selectRaw('COALESCE(SUM((order_items.quantity * order_items.price) - COALESCE(order_items.discount_allocated, 0) - COALESCE(order_items.commission_amount, 0) - COALESCE(order_items.marketplace_fee_allocated, 0) - COALESCE(order_items.online_transaction_fee_allocated, 0)), 0) as net_total')
            ->value('net_total');

        return [
            'wallet' => $wallet,
            'totalPurchasesSum' => $totalPurchasesSum,
            'totalEarningsSum' => $totalEarningsSum,
            'availableEarnings' => (float) (PayoutService::getEarningsForUser($user, 'private')['net'] ?? 0),
            'globalFreeListingLimit' => (int) Setting::get('private_listing_free_limit', '3'),
            'globalMaxListingLimit' => (int) Setting::get('private_listing_limit', '15'),
        ];
    }

    public static function ensureCustomer(User $user): ?RedirectResponse
    {
        if ($user->role === 'customer') {
            return null;
        }

        if (($user->is_private_seller ?? false) || $user->private_seller_kyc_status) {
            return null;
        }

        return redirect()->route('admin.users.index')->with('error', 'Not a customer.');
    }

    public static function ensureNotificationPrefs(User $user): array
    {
        $whatsappChannelOn = (string) Setting::get('notification_whatsapp_enabled', '1') === '1';

        NotificationPreference::seedDefaultsForUser((int) $user->id, $whatsappChannelOn);

        $user->load('notificationPreferences');
        $notificationPrefs = $user->notificationPreferences
            ->when(! $whatsappChannelOn, fn ($c) => $c->where('channel', '!=', 'whatsapp'))
            ->values();

        return [$notificationPrefs, $whatsappChannelOn];
    }

    /** @return list<array{label: string, route: string, active: string}> */
    public static function navItems(): array
    {
        return [
            ['label' => 'Overview', 'route' => 'admin.users.show', 'active' => 'admin.users.show'],
            ['label' => 'Edit Profile', 'route' => 'admin.users.profile', 'active' => 'admin.users.profile'],
            ['label' => 'Address', 'route' => 'admin.users.addresses', 'active' => 'admin.users.addresses'],
            ['label' => 'Alerts Preferences', 'route' => 'admin.users.alerts', 'active' => 'admin.users.alerts'],
            ['label' => 'Update Wallet', 'route' => 'admin.users.wallet', 'active' => 'admin.users.wallet'],
            ['label' => 'Purchase Promotion', 'route' => 'admin.users.promotions', 'active' => 'admin.users.promotions'],
            ['label' => 'Offer Free Listing', 'route' => 'admin.users.free-listing', 'active' => 'admin.users.free-listing'],
            ['label' => 'Listings', 'route' => 'admin.users.listings.index', 'active' => 'admin.users.listings.*'],
            ['label' => 'Transactions', 'route' => 'admin.users.transactions', 'active' => 'admin.users.transactions'],
            ['label' => 'Orders', 'route' => 'admin.users.orders', 'active' => 'admin.users.orders'],
            ['label' => 'Account Actions', 'route' => 'admin.users.account-actions', 'active' => 'admin.users.account-actions'],
        ];
    }
}
