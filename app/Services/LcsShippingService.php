<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Setting;

/**
 * Leopards checkout shipping. Prices come from the seller's listing — the
 * Leopards tariff API is not used anywhere in the order flow.
 */
class LcsShippingService
{
    public static function isEnabled(): bool
    {
        return (string) Setting::get('leopards_enabled', '0') === '1';
    }

    /**
     * @return array<string, mixed>
     */
    public static function calculateForItems(iterable $cartItems, ?Address $address = null, string $market = 'PK'): array
    {
        if ($market === 'AE') {
            return self::legacyZoneFallback($cartItems, $address, 'AE');
        }

        return SellerFixedShippingService::calculateForItems($cartItems, $address, 'leopards');
    }

    /**
     * Non-PK markets: keep existing zone-based shipping as single bucket.
     *
     * @return array<string, mixed>
     */
    protected static function legacyZoneFallback(iterable $cartItems, ?Address $address, string $market): array
    {
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $subtotal += (float) ($item->price ?? 0) * (int) ($item->quantity ?? 1);
        }
        $country = $address?->country ?? 'UAE';
        $zoneResult = ShippingService::calculate($subtotal, 0, $country, $market);

        return [
            'cost' => (float) ($zoneResult['cost'] ?? 0),
            'total_cost' => (float) ($zoneResult['cost'] ?? 0),
            'stores' => [],
            'options' => $zoneResult['options'] ?? [],
            'zone_name' => $zoneResult['zone']?->name,
            'carrier' => 'zone',
            'rates_available' => true,
            'unavailable_message' => null,
        ];
    }
}
