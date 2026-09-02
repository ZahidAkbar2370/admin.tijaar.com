<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\Store;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function packages(Request $request): JsonResponse
    {
        $user = $request->user();
        $sellerType = $user?->role === 'seller' ? 'business' : ($user?->is_private_seller ? 'private' : null);

        $query = PromotionPackage::active()->orderBy('sort_order');

        if ($sellerType) {
            $query->where(function ($q) use ($sellerType) {
                $q->where('seller_type_eligibility', $sellerType)
                    ->orWhere('seller_type_eligibility', 'both');
            });
        } else {
            $query->where('seller_type_eligibility', 'both');
        }

        // Customer-as-seller (no store): product packages only.
        if ($user && $user->role === 'customer' && !$user->is_private_seller) {
            $query->whereIn('type', ['featured_product', 'hot_sale']);
        }

        $packages = $query->get();

        return response()->json(['success' => true, 'packages' => $packages]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:promotion_packages,id',
            'product_id' => 'nullable|exists:products,id',
            'store_id' => 'nullable|exists:stores,id',
            'payment_method' => 'nullable|string|in:wallet',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $package = PromotionPackage::active()->findOrFail($request->package_id);
        $price = (float) $package->price;
        $productId = null;
        $storeId = null;

        if ($package->type === 'featured_product' || $package->type === 'hot_sale') {
            if (!$request->product_id) {
                return response()->json(['success' => false, 'message' => 'Product required'], 422);
            }
            $product = Product::findOrFail($request->product_id);
            $productId = $product->id;
            $storeId = $product->store_id;
            if ($user->role === 'seller') {
                $store = $user->seller?->store;
                if (!$store || $product->store_id !== $store->id) {
                    return response()->json(['success' => false, 'message' => 'Not your product'], 403);
                }
            } elseif ($user->role === 'customer') {
                if ((int) $product->seller_id !== (int) $user->id) {
                    return response()->json(['success' => false, 'message' => 'Not your listing'], 403);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Seller or customer seller required'], 403);
            }
        } elseif ($package->type === 'featured_shop' || $package->type === 'store_banner') {
            if (!$user->is_private_seller) {
                return response()->json(['success' => false, 'message' => 'Featured shop and store banner packages are for approved private sellers only.'], 403);
            }
            if (!$request->store_id) {
                return response()->json(['success' => false, 'message' => 'Store required'], 422);
            }
            $store = Store::findOrFail($request->store_id);
            $storeId = $store->id;
            if ($store->seller->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Not your store'], 403);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid package type'], 422);
        }

        $sellerType = $user->role === 'seller' ? 'business' : ($user->is_private_seller ? 'private' : 'private');
        $paymentMethod = $request->input('payment_method', 'wallet');
        $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');
        $walletBalance = (float) $wallet->balance;

        if ($price > 0) {
            if ($walletBalance < $price) {
                $earnings = ($user->role === 'seller' && !$user->seller?->store)
                    ? ['net' => 0]
                    : PayoutService::getEarningsForUser($user, $sellerType);
                $availableEarnings = (float) ($earnings['net'] ?? 0);
                $spendable = $walletBalance + $availableEarnings;
                if ($spendable < $price) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient balance. Wallet: ' . number_format($walletBalance, 2) . ' + Available earnings: ' . number_format($availableEarnings, 2) . ' = ' . number_format($spendable, 2) . ' ' . $wallet->currency . '. Package price: ' . number_format($price, 2) . ' ' . $wallet->currency . '. Top up your wallet to continue.',
                        'error_code' => 'insufficient_wallet',
                    ], 422);
                }
                PayoutService::creditWalletFromEarnings($user, $price - $walletBalance, $sellerType);
                $wallet->refresh();
                $walletBalance = (float) $wallet->balance;
            }
            if ($walletBalance < $price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance after crediting from earnings.',
                ], 422);
            }
        }

        $startsAt = now();
        $endsAt = now()->addDays($package->duration_days);
        $paymentRef = 'FREE';

        DB::beginTransaction();
        try {
            if ($price > 0 && $paymentMethod === 'wallet') {
                $newBalance = (float) $wallet->balance - $price;
                $wallet->update(['balance' => $newBalance]);
                $walletTransaction = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'package_purchase',
                    'amount' => -$price,
                    'balance_after' => $newBalance,
                    'reference_type' => 'promotion',
                    'reference_id' => null,
                    'description' => 'Payment for Product Promotion — ' . $package->name . ' (' . $package->duration_days . ' days)',
                    'meta' => [
                        'package_id' => $package->id,
                        'package_name' => $package->name,
                        'package_type' => $package->type,
                        'duration_days' => $package->duration_days,
                        'product_id' => $productId,
                        'store_id' => $storeId,
                    ],
                ]);
                $paymentRef = 'wallet:' . $walletTransaction->id;
            }

            $promotion = Promotion::create([
                'promotion_package_id' => $package->id,
                'product_id' => $productId,
                'store_id' => $storeId,
                'user_id' => $user->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
                'payment_ref' => $paymentRef,
            ]);

            if ($price > 0 && $paymentMethod === 'wallet' && isset($walletTransaction)) {
                $walletTransaction->update(['reference_id' => $promotion->id]);
            }

            if ($productId) {
                if ($package->type === 'featured_product') {
                    Product::where('id', $productId)->update(['is_featured' => true]);
                } elseif ($package->type === 'hot_sale') {
                    Product::where('id', $productId)->update(['is_hot' => true]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Purchase failed. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Package activated for ' . $package->duration_days . ' days. Featured label will be removed automatically when it expires.',
            'promotion' => $this->formatPromotionRecord($promotion->load(['package', 'product', 'store'])),
        ], 201);
    }

    public function eligibility(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $isSeller = $user->role === 'seller';
        $isCustomerSeller = $user->role === 'customer';
        if (!$isSeller && !$isCustomerSeller) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $featuredEligible = Promotion::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->whereHas('package', fn ($q) => $q->where('type', 'featured_product'))
            ->exists();
        $hotEligible = Promotion::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->whereHas('package', fn ($q) => $q->where('type', 'hot_sale'))
            ->exists();

        return response()->json([
            'success' => true,
            'featured_eligible' => $featuredEligible,
            'hot_eligible' => $hotEligible,
            'promote_url' => $isSeller ? '/seller/promote' : '/customer/promotion-packages',
        ]);
    }

    public function mySubscriptions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $promotions = Promotion::where('user_id', $user->id)
            ->with(['package', 'product', 'store'])
            ->orderByDesc('ends_at')
            ->get();

        $subscriptions = $promotions->map(fn (Promotion $p) => $this->formatPromotionRecord($p));

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
            'active_count' => $subscriptions->where('is_active', true)->count(),
        ]);
    }

    /**
     * Purchase history with product/store details and duration.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $promotions = Promotion::where('user_id', $user->id)
            ->with(['package', 'product', 'store'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        $items = collect($promotions->items())->map(fn (Promotion $p) => $this->formatPromotionRecord($p));

        return response()->json([
            'success' => true,
            'history' => $items,
            'pagination' => [
                'current_page' => $promotions->currentPage(),
                'last_page' => $promotions->lastPage(),
                'total' => $promotions->total(),
            ],
        ]);
    }

    private function formatPromotionRecord(Promotion $p): array
    {
        $endsAt = $p->ends_at;
        $now = now();
        $daysRemaining = $endsAt ? max(0, (int) $now->diffInDays($endsAt, false)) : 0;
        $isActive = $p->status === 'active' && $endsAt && $endsAt->isFuture();

        return [
            'id' => $p->id,
            'package_id' => $p->promotion_package_id,
            'package_name' => $p->package?->name,
            'package_type' => $p->package?->type,
            'package_type_label' => $this->typeLabel($p->package?->type),
            'duration_days' => $p->package?->duration_days,
            'price' => $p->package ? (float) $p->package->price : null,
            'starts_at' => $p->starts_at?->toIso8601String(),
            'ends_at' => $endsAt?->toIso8601String(),
            'status' => $p->status,
            'is_active' => $isActive,
            'days_remaining' => $daysRemaining,
            'product_id' => $p->product_id,
            'product_name' => $p->product?->name,
            'product_slug' => $p->product?->slug,
            'store_id' => $p->store_id,
            'store_name' => $p->store?->name,
            'store_slug' => $p->store?->slug,
            'payment_ref' => $p->payment_ref,
            'purchased_at' => $p->created_at?->toIso8601String(),
        ];
    }

    private function typeLabel(?string $type): string
    {
        return match ($type) {
            'featured_product' => 'Featured Product',
            'hot_sale' => 'Hot Sale / Flash Deal',
            'featured_shop' => 'Featured Shop',
            'store_banner' => 'Store Banner',
            default => 'Promotion',
        };
    }
}
