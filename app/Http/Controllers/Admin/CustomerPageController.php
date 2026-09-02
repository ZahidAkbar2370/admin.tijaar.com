<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\User;
use App\Services\Admin\CustomerAdminService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerPageController extends Controller
{
    public function show(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $stats = CustomerAdminService::overviewStats($user);

        return view('admin.users.pages.overview', array_merge(compact('user'), $stats));
    }

    public function profile(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        return view('admin.users.pages.profile', compact('user'));
    }

    public function addresses(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $user->load('addresses');

        return view('admin.users.pages.addresses', compact('user'));
    }

    public function alerts(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        [$notificationPrefs, $whatsappChannelOn] = CustomerAdminService::ensureNotificationPrefs($user);

        return view('admin.users.pages.alerts', compact('user', 'notificationPrefs', 'whatsappChannelOn'));
    }

    public function wallet(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $wallet = \App\Models\Wallet::getOrCreateForUser($user->id, 'PKR');

        return view('admin.users.pages.wallet', compact('user', 'wallet'));
    }

    public function promotions(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $promotionPackages = PromotionPackage::active()->orderBy('sort_order')->get();
        $userPromotions = Promotion::where('user_id', $user->id)->with('package')->orderByDesc('created_at')->limit(20)->get();
        $userProducts = Product::where('seller_id', $user->id)
            ->where('seller_type', 'private')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'name', 'status']);

        return view('admin.users.pages.promotions', compact('user', 'promotionPackages', 'userPromotions', 'userProducts'));
    }

    public function freeListing(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $stats = CustomerAdminService::overviewStats($user);

        return view('admin.users.pages.free-listing', array_merge(compact('user'), $stats));
    }

    public function transactions(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $wallet = \App\Models\Wallet::getOrCreateForUser($user->id, 'PKR');
        $walletTransactions = $wallet->transactions()->orderByDesc('created_at')->paginate(20);

        return view('admin.users.pages.transactions', compact('user', 'wallet', 'walletTransactions'));
    }

    public function orders(Request $request, User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $orderRole = $request->input('order_role', 'buyer');
        if (! in_array($orderRole, ['buyer', 'seller'], true)) {
            $orderRole = 'buyer';
        }

        if ($orderRole === 'seller') {
            $customerOrders = Order::query()
                ->whereHas('items', fn ($q) => $q->where('seller_id', $user->id)->where('seller_type', 'private'))
                ->with(['user:id,name,email'])
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            $customerOrders = Order::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.users.pages.orders', compact('user', 'customerOrders', 'orderRole'));
    }

    public function accountActions(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        return view('admin.users.pages.account-actions', compact('user'));
    }
}
