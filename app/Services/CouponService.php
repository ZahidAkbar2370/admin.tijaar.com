<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;

class CouponService
{
    public static function validate(string $code, Cart $cart): array
    {
        $coupon = Coupon::active()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid or expired coupon'];
        }

        if ($coupon->scope === 'store' && !$coupon->store_id) {
            return ['valid' => false, 'message' => 'Invalid coupon'];
        }

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return ['valid' => false, 'message' => 'Coupon usage limit reached'];
        }

        $subtotal = (float) $cart->subtotal;
        if ($subtotal < (float) $coupon->min_order_amount) {
            return [
                'valid' => false,
                'message' => 'Minimum order amount ' . number_format($coupon->min_order_amount, 2) . ' required',
            ];
        }

        $applicableTotal = self::getApplicableTotal($cart, $coupon);
        if ($applicableTotal <= 0) {
            return ['valid' => false, 'message' => 'Coupon does not apply to items in cart'];
        }

        $discount = self::calculateDiscount($coupon, $applicableTotal);
        if ($discount <= 0) {
            return ['valid' => false, 'message' => 'No discount applicable'];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => round($discount, 2),
            'applicable_total' => $applicableTotal,
        ];
    }

    public static function getApplicableTotal(Cart $cart, Coupon $coupon): float
    {
        $cart->load('items.product');
        $total = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            $itemStoreId = $product->store_id;

            if ($coupon->scope === 'store' && (int) $coupon->store_id !== (int) $itemStoreId) {
                continue;
            }

            $hasCategoryRestriction = $coupon->categories()->exists();
            $hasProductRestriction = $coupon->products()->exists();

            if ($hasCategoryRestriction && !$coupon->categories()->where('categories.id', $product->category_id)->exists()) {
                continue;
            }
            if ($hasProductRestriction && !$coupon->products()->where('products.id', $product->id)->exists()) {
                continue;
            }

            $total += (float) $item->price * $item->quantity;
        }

        return round($total, 2);
    }

    public static function calculateDiscount(Coupon $coupon, float $applicableTotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = $applicableTotal * ((float) $coupon->value / 100);
        } else {
            $discount = min((float) $coupon->value, $applicableTotal);
        }

        if ($coupon->max_discount && $discount > (float) $coupon->max_discount) {
            $discount = (float) $coupon->max_discount;
        }

        return round($discount, 2);
    }

    public static function incrementUsage(Coupon $coupon): void
    {
        $coupon->increment('used_count');
    }
}
