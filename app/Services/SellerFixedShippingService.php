<?php

namespace App\Services;

use App\Models\Address;

/**
 * Checkout shipping for Pakistan orders.
 *
 * Prices come from what the seller entered on the listing (shipping_mode +
 * shipping_cost_cached) — TCS and Leopards rate APIs are never called. Couriers
 * are used for tracking only, after the seller ships the parcel.
 */
class SellerFixedShippingService
{
    /**
     * Per-store breakdown for cart / checkout.
     *
     * @param  iterable  $cartItems  items with product, quantity, price loaded
     * @return array<string, mixed>
     */
    public static function calculateForItems(iterable $cartItems, ?Address $address, string $carrier): array
    {
        $destCity = trim((string) ($address?->city ?? ''));
        $destCountry = $address?->country ?? 'Pakistan';

        $groups = self::groupItemsBySeller($cartItems);
        if ($groups === []) {
            return [
                'cost' => 0.0,
                'total_cost' => 0.0,
                'stores' => [],
                'carrier' => $carrier,
                'zone_name' => self::carrierLabel($carrier),
                'rates_available' => false,
                'unavailable_message' => 'Cart is empty.',
            ];
        }

        $stores = [];
        $totalCost = 0.0;

        foreach ($groups as $key => $group) {
            $packageWeightKg = 0.0;
            $subtotal = 0.0;
            $storeShipping = 0.0;
            $lineItems = [];
            $hasCustomerPays = false;

            foreach ($group['items'] as $row) {
                $item = $row['item'];
                $product = $row['product'];
                $qty = max(1, (int) ($item->quantity ?? 1));
                $packageWeightKg += max(0.1, (float) ($product->weight_kg ?? 0.5)) * $qty;
                $subtotal += (float) ($item->price ?? $product->price) * $qty;

                $mode = $product->shipping_mode ?? 'customer_pays';
                $lineShipping = 0.0;
                if ($mode === 'customer_pays') {
                    $hasCustomerPays = true;
                    $lineShipping = round((float) ($product->shipping_cost_cached ?? 0) * $qty, 2);
                    $storeShipping += $lineShipping;
                }

                $lineItems[] = [
                    'product_id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'quantity' => $qty,
                    'shipping_mode' => $mode,
                    'line_shipping' => $lineShipping,
                    'source' => match ($mode) {
                        'free_shipping' => 'free_shipping',
                        'included_in_price' => 'included_in_price',
                        default => 'seller_fixed',
                    },
                ];
            }

            $storeShipping = round($storeShipping, 2);
            $totalCost += $storeShipping;

            $stores[] = [
                'group_key' => $key,
                'store_id' => $group['store_id'],
                'seller_id' => $group['seller_id'],
                'store_name' => $group['store_name'],
                'cost' => $storeShipping,
                'weight_kg' => round(max(0.1, $packageWeightKg), 3),
                'origin_city' => $group['origin_city'],
                'destination_city' => $destCity,
                'destination_country' => $destCountry,
                'subtotal' => round($subtotal, 2),
                'items' => $lineItems,
                'carrier' => $carrier,
                'source' => $hasCustomerPays ? 'seller_fixed' : 'seller_covered',
                'rate_available' => true,
                'shipping_summary' => $hasCustomerPays
                    ? 'Customer pays delivery (seller price)'
                    : 'Free / included in price',
            ];
        }

        return [
            'cost' => round($totalCost, 2),
            'total_cost' => round($totalCost, 2),
            'stores' => $stores,
            'carrier' => $carrier,
            'zone_name' => self::carrierLabel($carrier),
            'rates_available' => true,
            'unavailable_message' => null,
        ];
    }

    public static function carrierLabel(string $carrier): string
    {
        return match (strtolower($carrier)) {
            'tcs' => 'TCS',
            'leopards', 'lcs' => 'Leopard / LCS',
            'postex' => 'PostEx',
            'dex' => 'Dex',
            'daewoo_fastex' => 'Daewoo FastEx',
            'mnp' => 'M&P',
            'baloch_cargo' => 'Baloch Cargo',
            default => 'Courier',
        };
    }

    /**
     * @return array<string, array{store_id: ?int, seller_id: ?int, store_name: string, origin_city: string, items: list<array{item: mixed, product: mixed}>}>
     */
    public static function groupItemsBySeller(iterable $cartItems): array
    {
        $groups = [];

        foreach ($cartItems as $item) {
            $product = $item->product ?? null;
            if (!$product) {
                continue;
            }

            $storeId = $product->store_id ? (int) $product->store_id : null;
            $sellerId = $product->seller_id ? (int) $product->seller_id : null;
            $key = $storeId ? 'store:' . $storeId : 'seller:' . ($sellerId ?? 0);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'store_id' => $storeId,
                    'seller_id' => $sellerId,
                    'store_name' => SellerOriginResolver::storeDisplayName($product),
                    'origin_city' => (string) SellerOriginResolver::forProduct($product),
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = ['item' => $item, 'product' => $product];
        }

        return $groups;
    }
}
