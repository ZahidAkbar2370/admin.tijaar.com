<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Setting;

/**
 * Buyer checkout fees (added to customer total) vs seller-side deductions (from product price, not shipping).
 */
class MarketplaceFeeService
{
    /** @deprecated Use buyerMarketplaceFeeType — legacy key fallback. */
    public static function marketplaceFeeType(): string
    {
        return self::buyerMarketplaceFeeType();
    }

    /** @deprecated Use buyerMarketplaceFeeValue */
    public static function marketplaceFeeValue(): float
    {
        return self::buyerMarketplaceFeeValue();
    }

    /** @deprecated Use buyerOnlineTransactionFeeType */
    public static function onlineTransactionFeeType(): string
    {
        return self::buyerOnlineTransactionFeeType();
    }

    /** @deprecated Use buyerOnlineTransactionFeeValue */
    public static function onlineTransactionFeeValue(): float
    {
        return self::buyerOnlineTransactionFeeValue();
    }

    public static function buyerMarketplaceFeeType(): string
    {
        return self::feeType('buyer_marketplace_fee_type', 'marketplace_fee_type');
    }

    public static function buyerMarketplaceFeeValue(): float
    {
        return self::feeValue('buyer_marketplace_fee_value', 'marketplace_fee_value');
    }

    public static function buyerOnlineTransactionFeeType(): string
    {
        return self::feeType('buyer_online_transaction_fee_type', 'online_transaction_fee_type');
    }

    public static function buyerOnlineTransactionFeeValue(): float
    {
        return self::feeValue('buyer_online_transaction_fee_value', 'online_transaction_fee_value');
    }

    public static function privateSellerMarketplaceFeeType(): string
    {
        return self::feeType('private_seller_marketplace_fee_type', 'marketplace_fee_type');
    }

    public static function privateSellerMarketplaceFeeValue(): float
    {
        return self::feeValue('private_seller_marketplace_fee_value', 'marketplace_fee_value');
    }

    public static function privateSellerOnlineTransactionFeeType(): string
    {
        return self::feeType('private_seller_online_transaction_fee_type', 'online_transaction_fee_type');
    }

    public static function privateSellerOnlineTransactionFeeValue(): float
    {
        return self::feeValue('private_seller_online_transaction_fee_value', 'online_transaction_fee_value');
    }

    public static function sellerCommissionType(): string
    {
        $t = strtolower((string) Setting::get('seller_commission_type', 'percentage'));

        return in_array($t, ['fixed', 'percentage'], true) ? $t : 'percentage';
    }

    public static function sellerCommissionValue(): float
    {
        return max(0, (float) Setting::get('seller_commission_value', '2'));
    }

    public static function privateSellerCommissionType(): string
    {
        $t = strtolower((string) Setting::get('private_seller_commission_type', 'percentage'));

        return in_array($t, ['fixed', 'percentage'], true) ? $t : 'percentage';
    }

    public static function privateSellerCommissionValue(): float
    {
        return max(0, (float) Setting::get('private_seller_commission_value', '2'));
    }

    /** Merchandise order price used for fees (subtotal − discount). Shipping is excluded. */
    public static function orderPrice(float $subtotal, float $discount = 0): float
    {
        return max(0, round($subtotal - $discount, 2));
    }

    public static function calcFeeOnOrderPrice(string $type, float $value, float $orderPrice): float
    {
        $orderPrice = max(0, $orderPrice);
        $value = max(0, $value);
        if ($orderPrice <= 0 || $value <= 0) {
            return 0.0;
        }
        if ($type === 'percentage') {
            return round($orderPrice * ($value / 100), 2);
        }

        return round(min($value, $orderPrice), 2);
    }

    public static function buyerMarketplaceFee(float $orderPrice): float
    {
        return self::calcFeeOnOrderPrice(
            self::buyerMarketplaceFeeType(),
            self::buyerMarketplaceFeeValue(),
            $orderPrice
        );
    }

    public static function buyerOnlineTransactionFee(float $orderPrice, ?string $paymentMethod = null): float
    {
        if ($paymentMethod !== null && self::isCodOnly($paymentMethod)) {
            return 0.0;
        }

        return self::calcFeeOnOrderPrice(
            self::buyerOnlineTransactionFeeType(),
            self::buyerOnlineTransactionFeeValue(),
            $orderPrice
        );
    }

    /** Deducted from customer-seller earnings (product line only). */
    public static function privateSellerMarketplaceFee(float $productLineTotal): float
    {
        return self::calcFeeOnOrderPrice(
            self::privateSellerMarketplaceFeeType(),
            self::privateSellerMarketplaceFeeValue(),
            $productLineTotal
        );
    }

    /** Deducted from customer-seller earnings on online-paid orders (product line only). */
    public static function privateSellerOnlineTransactionFee(float $productLineTotal, ?string $paymentMethod = null): float
    {
        if ($paymentMethod !== null && self::isCodOnly($paymentMethod)) {
            return 0.0;
        }

        return self::calcFeeOnOrderPrice(
            self::privateSellerOnlineTransactionFeeType(),
            self::privateSellerOnlineTransactionFeeValue(),
            $productLineTotal
        );
    }

    /** @deprecated Alias for buyerMarketplaceFee */
    public static function marketplaceFee(float $orderPrice): float
    {
        return self::buyerMarketplaceFee($orderPrice);
    }

    /** @deprecated Alias for buyerOnlineTransactionFee */
    public static function onlineTransactionFee(float $orderPrice, ?string $paymentMethod = null): float
    {
        return self::buyerOnlineTransactionFee($orderPrice, $paymentMethod);
    }

    public static function isCodOnly(?string $paymentMethod): bool
    {
        $m = strtolower(trim((string) $paymentMethod));

        return $m === '' || $m === 'cod';
    }

    /**
     * Full customer payable at checkout = merchandise + shipping + buyer fees.
     */
    public static function customerTotal(
        float $subtotal,
        float $shipping,
        float $discount = 0,
        ?string $paymentMethod = null
    ): array {
        $orderPrice = self::orderPrice($subtotal, $discount);
        $marketplaceFee = self::buyerMarketplaceFee($orderPrice);
        $transactionFee = self::buyerOnlineTransactionFee($orderPrice, $paymentMethod);
        $total = max(0, round($orderPrice + $shipping + $marketplaceFee + $transactionFee, 2));

        return [
            'order_price' => $orderPrice,
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'marketplace_fee' => $marketplaceFee,
            'marketplace_fee_type' => self::buyerMarketplaceFeeType(),
            'marketplace_fee_value' => self::buyerMarketplaceFeeValue(),
            'online_transaction_fee' => $transactionFee,
            'online_transaction_fee_type' => self::buyerOnlineTransactionFeeType(),
            'online_transaction_fee_value' => self::buyerOnlineTransactionFeeValue(),
            'total' => $total,
        ];
    }

    /** Seller-side fee allocations for a private listing line (product price after discount, not shipping). */
    public static function privateSellerLineDeductions(float $effectiveLineTotal, ?string $paymentMethod = null): array
    {
        $marketplace = self::privateSellerMarketplaceFee($effectiveLineTotal);
        $online = self::privateSellerOnlineTransactionFee($effectiveLineTotal, $paymentMethod);

        return [
            'marketplace_fee_allocated' => $marketplace,
            'online_transaction_fee_allocated' => $online,
            'total_deductions' => round($marketplace + $online, 2),
        ];
    }

    /** Net seller earnings for one order line after commission + seller fee allocations. */
    public static function sellerLineNet(
        float $quantity,
        float $unitPrice,
        float $discountAllocated,
        float $commissionAmount,
        float $marketplaceFeeAllocated = 0,
        float $onlineTransactionFeeAllocated = 0
    ): float {
        $effective = max(0, round($quantity * $unitPrice - $discountAllocated, 2));

        return max(0, round(
            $effective - $commissionAmount - $marketplaceFeeAllocated - $onlineTransactionFeeAllocated,
            2
        ));
    }

    public static function publicConfig(): array
    {
        return [
            'marketplace_fee_type' => self::buyerMarketplaceFeeType(),
            'marketplace_fee_value' => self::buyerMarketplaceFeeValue(),
            'online_transaction_fee_type' => self::buyerOnlineTransactionFeeType(),
            'online_transaction_fee_value' => self::buyerOnlineTransactionFeeValue(),
            'buyer_marketplace_fee_type' => self::buyerMarketplaceFeeType(),
            'buyer_marketplace_fee_value' => self::buyerMarketplaceFeeValue(),
            'buyer_online_transaction_fee_type' => self::buyerOnlineTransactionFeeType(),
            'buyer_online_transaction_fee_value' => self::buyerOnlineTransactionFeeValue(),
            'seller_commission_type' => self::sellerCommissionType(),
            'seller_commission_value' => self::sellerCommissionValue(),
            'private_seller_commission_type' => self::privateSellerCommissionType(),
            'private_seller_commission_value' => self::privateSellerCommissionValue(),
            'private_seller_marketplace_fee_type' => self::privateSellerMarketplaceFeeType(),
            'private_seller_marketplace_fee_value' => self::privateSellerMarketplaceFeeValue(),
            'private_seller_online_transaction_fee_type' => self::privateSellerOnlineTransactionFeeType(),
            'private_seller_online_transaction_fee_value' => self::privateSellerOnlineTransactionFeeValue(),
            'online_transaction_fee_applies_to' => 'online_payments',
        ];
    }

    public static function syncGlobalSellerCommissionRule(): void
    {
        $type = self::sellerCommissionType();
        $value = self::sellerCommissionValue();

        $rule = Commission::query()
            ->where('scope_type', 'global')
            ->whereNull('scope_id')
            ->orderByDesc('priority')
            ->first();

        if ($rule) {
            $rule->update([
                'commission_type' => $type,
                'value' => $value,
                'is_active' => true,
                'priority' => max(1, (int) $rule->priority),
            ]);

            return;
        }

        Commission::create([
            'scope_type' => 'global',
            'scope_id' => null,
            'seller_type' => null,
            'commission_type' => $type,
            'value' => $value,
            'priority' => 1,
            'is_active' => true,
        ]);
    }

    public static function syncPrivateSellerCommissionRule(): void
    {
        $type = self::privateSellerCommissionType();
        $value = self::privateSellerCommissionValue();

        $rule = Commission::query()
            ->where('scope_type', 'seller_type')
            ->where('seller_type', 'private')
            ->orderByDesc('priority')
            ->first();

        if ($rule) {
            $rule->update([
                'commission_type' => $type,
                'value' => $value,
                'is_active' => true,
                'priority' => max(5, (int) $rule->priority),
            ]);

            return;
        }

        Commission::create([
            'scope_type' => 'seller_type',
            'scope_id' => null,
            'seller_type' => 'private',
            'commission_type' => $type,
            'value' => $value,
            'priority' => 5,
            'is_active' => true,
        ]);
    }

    private static function feeType(string $primaryKey, string $legacyKey): string
    {
        $t = strtolower((string) Setting::get($primaryKey, Setting::get($legacyKey, 'fixed')));

        return in_array($t, ['fixed', 'percentage'], true) ? $t : 'fixed';
    }

    private static function feeValue(string $primaryKey, string $legacyKey): float
    {
        $primary = Setting::get($primaryKey);
        if ($primary !== null && $primary !== '') {
            return max(0, (float) $primary);
        }

        return max(0, (float) Setting::get($legacyKey, '0'));
    }
}
