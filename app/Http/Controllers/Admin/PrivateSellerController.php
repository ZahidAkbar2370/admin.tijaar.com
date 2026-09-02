<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PrivateSellerController extends Controller
{
    public function index(Request $request): View
    {
        $overview = [
            'total_private_sellers' => User::where('is_private_seller', true)->count(),
            'pending_kyc' => User::where('private_seller_kyc_status', 'pending')->count(),
            'total_listings' => Product::where('seller_type', 'private')->count(),
            'active_listings' => Product::where('seller_type', 'private')->where('status', 'published')->count(),
            'expired_listings' => Product::where('seller_type', 'private')->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
        ];

        $pendingKyc = User::where('private_seller_kyc_status', 'pending')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $topPrivateSellers = User::where('is_private_seller', true)
            ->withCount(['products' => fn ($q) => $q->where('seller_type', 'private')->where('status', 'published')])
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();

        $listingsUsage = User::where('is_private_seller', true)
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('COUNT(products.id) as listing_count')
            ->leftJoin('products', function ($j) {
                $j->on('products.seller_id', '=', 'users.id')
                    ->where('products.seller_type', '=', 'private');
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('listing_count')
            ->paginate(20);

        return view('admin.private-sellers.index', compact('overview', 'topPrivateSellers', 'listingsUsage', 'pendingKyc'));
    }

    public function approveKyc(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer private-seller applicant.');
        }
        if ($user->private_seller_kyc_status !== 'pending' && !$request->boolean('force')) {
            return back()->with('error', 'No pending private seller KYC for this user.');
        }

        $prefs = $user->preferences ?? [];
        $kyc = $prefs['private_seller_kyc'] ?? [];

        $user->update([
            'is_private_seller' => true,
            'private_seller_kyc_status' => 'approved',
            'phone' => $user->phone ?: ($kyc['phone'] ?? $user->phone),
        ]);

        // Minimal Seller + Store for payouts / address context if missing
        $seller = $user->seller;
        if (!$seller) {
            $seller = Seller::create([
                'user_id' => $user->id,
                'status' => 'approved',
                'kyc_status' => 'verified',
                'bank_name' => $kyc['bank_name'] ?? null,
                'bank_account_number' => $kyc['bank_account_number'] ?? null,
                'bank_account_holder' => $kyc['bank_account_holder'] ?? null,
                'approved_at' => now(),
                'approved_by' => $request->user()?->id,
            ]);
        } else {
            $seller->update([
                'status' => 'approved',
                'kyc_status' => 'verified',
                'approved_at' => $seller->approved_at ?? now(),
            ]);
        }

        if (!$seller->store) {
            Store::create([
                'seller_id' => $seller->id,
                'name' => $user->name ?: ('Seller ' . $user->id),
                'slug' => Str::slug(($user->name ?: 'seller') . '-' . $user->id),
                'phone' => $kyc['phone'] ?? $user->phone,
                'address' => $kyc['address'] ?? null,
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'Private seller KYC approved.');
    }

    public function rejectKyc(Request $request, User $user): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);

        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer private-seller applicant.');
        }

        $prefs = $user->preferences ?? [];
        $kyc = $prefs['private_seller_kyc'] ?? [];
        $kyc['rejection_reason'] = $request->input('rejection_reason');
        $kyc['rejected_at'] = now()->toIso8601String();
        $prefs['private_seller_kyc'] = $kyc;

        $user->update([
            'private_seller_kyc_status' => 'rejected',
            'preferences' => $prefs,
            'is_private_seller' => false,
        ]);

        return back()->with('success', 'Private seller KYC rejected.');
    }
}
