<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminUserActionController extends Controller
{
    public function adjustWallet(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'note' => 'nullable|string|max:500',
        ]);

        $amount = round((float) $request->amount, 2);
        $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');
        $newBalance = round((float) $wallet->balance + $amount, 2);

        if ($newBalance < 0) {
            return back()->with('error', 'Wallet balance cannot go below zero.');
        }

        DB::transaction(function () use ($wallet, $amount, $newBalance, $user, $request) {
            $wallet->update(['balance' => $newBalance]);
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $amount > 0 ? 'deposit' : 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => 'admin_adjustment',
                'reference_id' => auth()->id(),
                'description' => $request->input('note')
                    ?: (($amount > 0 ? 'Payment Added to Wallet' : 'Wallet Adjustment') . ' by ' . auth()->user()?->name),
                'meta' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()?->name,
                    'purpose' => 'admin_adjustment',
                ],
            ]);
        });

        return redirect()->route($this->walletRoute($user), $user)->with('success', 'Wallet updated. New balance: ' . number_format($newBalance, 2) . ' PKR');
    }

    public function assignPromotion(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:promotion_packages,id',
            'product_id' => 'nullable|exists:products,id',
            'store_id' => 'nullable|exists:stores,id',
            'payment_status' => 'required|in:paid,pending',
            'payment_method' => 'nullable|in:admin,wallet,link',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $package = PromotionPackage::active()->findOrFail($request->package_id);
        $productId = $request->product_id ? (int) $request->product_id : null;
        $storeId = $request->store_id ? (int) $request->store_id : null;

        if (in_array($package->type, ['featured_product', 'hot_sale'], true) && !$productId) {
            return back()->with('error', 'Select a product for this package.');
        }
        if (in_array($package->type, ['featured_shop', 'store_banner'], true) && !$storeId) {
            return back()->with('error', 'Select a store for this package.');
        }

        $paymentStatus = $request->input('payment_status');
        $paymentMethod = $request->input('payment_method', $paymentStatus === 'paid' ? 'admin' : 'link');
        $price = (float) $package->price;
        $token = $paymentStatus === 'pending' ? Str::random(48) : null;
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/');
        $paymentLink = $token ? "{$frontendUrl}/customer/promotion-packages?pay={$token}" : null;

        DB::beginTransaction();
        try {
            $paymentRef = 'admin:' . auth()->id();
            $paidBy = 'admin';

            if ($paymentStatus === 'paid' && $paymentMethod === 'wallet' && $price > 0) {
                $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');
                if ((float) $wallet->balance < $price) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient wallet balance for this package.');
                }
                $newBalance = (float) $wallet->balance - $price;
                $wallet->update(['balance' => $newBalance]);
                $txn = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'package_purchase',
                    'amount' => -$price,
                    'balance_after' => $newBalance,
                    'reference_type' => 'promotion',
                    'description' => 'Payment for Product Promotion — ' . $package->name,
                    'meta' => ['package_id' => $package->id, 'assigned_by_admin' => auth()->id()],
                ]);
                $paymentRef = 'wallet:' . $txn->id;
                $paidBy = 'wallet';
            }

            $promotion = Promotion::create([
                'promotion_package_id' => $package->id,
                'product_id' => $productId,
                'store_id' => $storeId,
                'user_id' => $user->id,
                'starts_at' => $paymentStatus === 'paid' ? now() : null,
                'ends_at' => $paymentStatus === 'paid' ? now()->addDays($package->duration_days) : null,
                'status' => $paymentStatus === 'paid' ? 'active' : 'pending',
                'payment_ref' => $paymentRef,
                'payment_status' => $paymentStatus,
                'assigned_by_user_id' => auth()->id(),
                'paid_by' => $paymentStatus === 'paid' ? $paidBy : null,
                'admin_note' => $request->input('admin_note'),
                'payment_link_token' => $token,
            ]);

            if ($paymentStatus === 'paid' && $productId) {
                if ($package->type === 'featured_product') {
                    Product::where('id', $productId)->update(['is_featured' => true]);
                } elseif ($package->type === 'hot_sale') {
                    Product::where('id', $productId)->update(['is_hot' => true]);
                }
            }

            if (isset($txn)) {
                $txn->update(['reference_id' => $promotion->id]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Could not assign promotion.');
        }

        $msg = $paymentStatus === 'paid'
            ? 'Promotion activated for ' . $package->duration_days . ' days.'
            : 'Promotion pending payment. Share link: ' . ($paymentLink ?? '—');

        return redirect()->route($this->promotionsRoute($user), $user)->with('success', $msg);
    }

    private function walletRoute(User $user): string
    {
        return $user->role === 'seller' ? 'admin.sellers.wallet' : 'admin.users.wallet';
    }

    private function promotionsRoute(User $user): string
    {
        return $user->role === 'seller' ? 'admin.sellers.promotions' : 'admin.users.promotions';
    }
}
