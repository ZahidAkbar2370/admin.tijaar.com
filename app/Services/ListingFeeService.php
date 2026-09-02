<?php

namespace App\Services;

use App\Mail\ListingPendingApprovalMail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Mail;

/**
 * Private listing fee: debit wallet / complete after gateway deposit, then publish.
 */
class ListingFeeService
{
    /** Online methods allowed for listing fee (COD / partial not applicable). */
    public static function allowedGateways(): array
    {
        $methods = ['wallet'];
        if ((string) Setting::get('stripe_enabled', '0') === '1') {
            $methods[] = 'stripe';
        }
        if ((string) Setting::get('jazzcash_enabled', '0') === '1') {
            $methods[] = 'jazzcash';
        }
        if ((string) Setting::get('easypaisa_enabled', '0') === '1') {
            $methods[] = 'easypaisa';
        }

        return $methods;
    }

    public static function isGatewayAllowed(string $method): bool
    {
        return in_array($method, self::allowedGateways(), true);
    }

    /**
     * After a listing_fee deposit is credited to the wallet, debit the fee and publish the product.
     */
    public static function applyAfterDeposit(WalletDeposit $deposit): bool
    {
        $meta = is_array($deposit->gateway_response) ? $deposit->gateway_response : [];
        if (($meta['purpose'] ?? null) !== 'listing_fee' || empty($meta['product_id'])) {
            return false;
        }

        $fee = (float) $deposit->amount;
        $productId = (int) $meta['product_id'];
        $product = Product::query()
            ->where('id', $productId)
            ->where('seller_type', 'private')
            ->where('seller_id', $deposit->user_id)
            ->first();

        if (!$product || !in_array($product->status, ['draft', 'unpublished'], true)) {
            return false;
        }

        $wallet = Wallet::getOrCreateForUser($deposit->user_id, $deposit->currency ?? 'PKR');
        $wallet->refresh();
        if ((float) $wallet->balance < $fee) {
            return false;
        }

        $afterFee = (float) $wallet->balance - $fee;
        $wallet->update(['balance' => $afterFee]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'listing_fee',
            'amount' => -$fee,
            'balance_after' => $afterFee,
            'reference_type' => 'product',
            'reference_id' => $product->id,
            'description' => 'Payment for Listing Fee — product #' . $product->id,
            'meta' => ['product_id' => $product->id, 'deposit_id' => $deposit->id],
        ]);

        self::publishProduct($product);

        return true;
    }

    /**
     * Debit listing fee from an already-funded wallet and publish.
     */
    public static function chargeWalletAndPublish($user, Product $product, float $fee): bool
    {
        $wallet = Wallet::getOrCreateForUser($user->id, 'PKR');
        if ((float) $wallet->balance < $fee) {
            return false;
        }

        $newBalance = (float) $wallet->balance - $fee;
        $wallet->update(['balance' => $newBalance]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'listing_fee',
            'amount' => -$fee,
            'balance_after' => $newBalance,
            'reference_type' => 'product',
            'reference_id' => $product->id,
            'description' => 'Payment for Listing Fee — product #' . $product->id,
            'meta' => ['product_id' => $product->id],
        ]);

        self::publishProduct($product);

        return true;
    }

    public static function publishProduct(Product $product): void
    {
        $user = $product->sellerUser;
        $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
        $expiryDays = (int) Setting::get('private_listing_expiry_days', '30');

        $product->status = $approvalRequired ? 'pending' : 'published';
        $product->oos_auto_inactive = false;
        if ($expiryDays > 0 && !$product->expires_at) {
            $product->expires_at = now()->addDays($expiryDays);
        }
        $product->save();

        if ($approvalRequired && $user?->email) {
            try {
                Mail::to($user->email)->send(new ListingPendingApprovalMail(
                    $user->name ?: 'Customer',
                    $product->name,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}