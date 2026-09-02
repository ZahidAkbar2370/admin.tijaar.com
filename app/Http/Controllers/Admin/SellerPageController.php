<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\User;
use App\Services\Admin\CustomerAdminService;
use App\Services\Admin\SellerAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerPageController extends Controller
{
    public function show(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $stats = SellerAdminService::overviewStats($user);

        return view('admin.sellers.pages.overview', array_merge(compact('user'), $stats));
    }

    public function profile(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        return view('admin.sellers.pages.profile', compact('user'));
    }

    public function kyc(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller');

        return view('admin.sellers.pages.kyc', compact('user'));
    }

    public function storePage(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller.store');

        return view('admin.sellers.pages.store', compact('user'));
    }

    public function addresses(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('addresses');

        return view('admin.sellers.pages.addresses', compact('user'));
    }

    public function alerts(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        [$notificationPrefs, $whatsappChannelOn] = CustomerAdminService::ensureNotificationPrefs($user);

        return view('admin.sellers.pages.alerts', compact('user', 'notificationPrefs', 'whatsappChannelOn'));
    }

    public function wallet(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $wallet = \App\Models\Wallet::getOrCreateForUser($user->id, 'PKR');

        return view('admin.sellers.pages.wallet', compact('user', 'wallet'));
    }

    public function promotions(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller.store');
        $promotionPackages = PromotionPackage::active()->orderBy('sort_order')->get();
        $userPromotions = Promotion::where('user_id', $user->id)->with('package')->orderByDesc('created_at')->limit(20)->get();
        $userProducts = Product::where('seller_id', $user->id)->where('seller_type', 'business')->orderByDesc('id')->limit(100)->get(['id', 'name', 'status']);
        $userStores = $user->seller?->store ? collect([$user->seller->store]) : collect();

        return view('admin.sellers.pages.promotions', compact('user', 'promotionPackages', 'userPromotions', 'userProducts', 'userStores'));
    }

    public function transactions(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $wallet = \App\Models\Wallet::getOrCreateForUser($user->id, 'PKR');
        $walletTransactions = $wallet->transactions()->orderByDesc('created_at')->paginate(20);

        return view('admin.sellers.pages.transactions', compact('user', 'wallet', 'walletTransactions'));
    }

    public function orders(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller.store');
        $storeId = $user->seller?->store?->id;
        $sellerOrders = Order::query()
            ->when($storeId, fn ($q) => $q->whereHas('items', fn ($i) => $i->where('store_id', $storeId)), fn ($q) => $q->whereRaw('0 = 1'))
            ->with(['user:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sellers.pages.orders', compact('user', 'sellerOrders', 'storeId'));
    }

    public function accountActions(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller');

        return view('admin.sellers.pages.account-actions', compact('user'));
    }
}
