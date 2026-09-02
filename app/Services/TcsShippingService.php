<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Setting;

/**
 * TCS checkout shipping. Prices come from the seller's listing — the TCS tariff
 * API is not used anywhere in the order flow.
 */
class TcsShippingService
{
    public static function isEnabled(): bool
    {
        return (string) Setting::get('tcs_enabled', '0') === '1';
    }

    /**
     * @return array<string, mixed>
     */
    public static function calculateForItems(iterable $cartItems, ?Address $address = null, string $market = 'PK'): array
    {
        if ($market === 'AE') {
            return LcsShippingService::calculateForItems($cartItems, $address, 'AE');
        }

        return SellerFixedShippingService::calculateForItems($cartItems, $address, 'tcs');
    }
}
