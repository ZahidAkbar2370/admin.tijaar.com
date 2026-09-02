<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\Setting;

/**
 * Pakistan checkout shipping.
 *
 * Costs always come from the seller's listing (SellerFixedShippingService); the
 * courier only decides which tracking API is polled later, so both providers
 * quote the same price and the seller can still change carrier when shipping.
 */
class PkCourierShippingService
{
    public static function enabledProviders(): array
    {
        return \App\Support\CourierCatalog::enabledValues();
    }

    public static function isValidProvider(?string $provider): bool
    {
        return \App\Support\CourierCatalog::isValid($provider);
    }

    public static function activeProvider(): string
    {
        $enabled = self::enabledProviders();
        if ($enabled === []) {
            return 'leopards';
        }

        return $enabled[0];
    }

    /**
     * @return array<string, mixed>
     */
    public static function calculateForProvider(string $provider, iterable $cartItems, ?Address $address = null, string $market = 'PK'): array
    {
        $provider = \App\Support\CourierCatalog::normalize($provider);

        return SellerFixedShippingService::calculateForItems($cartItems, $address, $provider ?: 'manual');
    }

    /**
     * @return array<string, mixed>
     */
    public static function calculateForItems(iterable $cartItems, ?Address $address = null, string $market = 'PK', ?string $preferredProvider = null): array
    {
        if ($market === 'AE') {
            return SellerFixedShippingService::calculateForItems($cartItems, $address, 'manual');
        }

        // Cart items are consumed once per provider, so materialise them first.
        $items = is_array($cartItems) ? $cartItems : iterator_to_array($cartItems);

        $enabled = self::enabledProviders();
        if ($enabled === []) {
            // Seller-priced shipping does not depend on a courier being enabled;
            // the carrier is chosen by the seller when they ship.
            $result = SellerFixedShippingService::calculateForItems($items, $address, 'manual');
            $result['selected_courier'] = null;
            $result['courier_options'] = [];
            $result['zone_name'] = 'Standard delivery';

            return $result;
        }

        $options = [];
        foreach ($enabled as $provider) {
            $result = self::calculateForProvider($provider, $items, $address, $market);
            $options[] = [
                'id' => $provider,
                'carrier' => $provider,
                'label' => SellerFixedShippingService::carrierLabel($provider),
                'cost' => (float) ($result['total_cost'] ?? $result['cost'] ?? 0),
                'total_cost' => (float) ($result['total_cost'] ?? $result['cost'] ?? 0),
                'stores' => $result['stores'] ?? [],
                'rates_available' => (bool) ($result['rates_available'] ?? false),
                'unavailable_message' => $result['unavailable_message'] ?? null,
                'zone_name' => $result['zone_name'] ?? SellerFixedShippingService::carrierLabel($provider),
            ];
        }

        $preferred = \App\Support\CourierCatalog::normalize(
            (string) ($preferredProvider ?: self::activeProvider())
        );
        $selected = null;
        foreach ($options as $opt) {
            if ($opt['id'] === $preferred && $opt['rates_available']) {
                $selected = $opt;
                break;
            }
        }
        if ($selected === null) {
            foreach ($options as $opt) {
                if ($opt['rates_available']) {
                    $selected = $opt;
                    break;
                }
            }
        }
        if ($selected === null) {
            $selected = $options[0];
        }

        $anyAvailable = collect($options)->contains(fn ($o) => $o['rates_available']);

        return [
            'cost' => (float) $selected['cost'],
            'total_cost' => (float) $selected['total_cost'],
            'stores' => $selected['stores'],
            'carrier' => $selected['carrier'],
            'zone_name' => $selected['zone_name'],
            'rates_available' => $anyAvailable,
            'unavailable_message' => $anyAvailable
                ? null
                : (collect($options)->pluck('unavailable_message')->filter()->unique()->implode(' ')
                    ?: 'Shipping is unavailable for this cart.'),
            'selected_courier' => $selected['id'],
            'courier_options' => $options,
        ];
    }

    /**
     * Recompute the per-store shipping split for an existing order.
     *
     * @return array{stores: array<int, array<string, mixed>>}
     */
    public static function breakdownForOrder(Order $order): array
    {
        $pseudoItems = $order->items->map(fn ($oi) => (object) [
            'quantity' => $oi->quantity,
            'price' => $oi->price,
            'product' => $oi->product,
        ]);

        $provider = \App\Support\CourierCatalog::normalize((string) ($order->shipping_method ?? ''));
        if (! self::isValidProvider($provider)) {
            $provider = self::activeProvider();
        }

        return self::calculateForProvider(
            $provider,
            $pseudoItems,
            $order->shippingAddress,
            $order->market === 'AE' ? 'AE' : 'PK'
        );
    }
}
