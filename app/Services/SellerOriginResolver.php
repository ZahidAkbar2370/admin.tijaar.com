<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

/**
 * Resolve parcel origin city for store sellers and private (customer) sellers.
 */
class SellerOriginResolver
{
    public static function forProduct(Product $product): string
    {
        $store = $product->relationLoaded('store') ? $product->store : $product->store()->first();
        $city = trim((string) ($store?->city ?? ''));
        if ($city !== '') {
            return $city;
        }

        $sellerUser = $product->relationLoaded('sellerUser')
            ? $product->sellerUser
            : ($product->seller_id ? User::with('addresses')->find($product->seller_id) : null);

        if ($sellerUser) {
            if (!$sellerUser->relationLoaded('addresses')) {
                $sellerUser->load('addresses');
            }
            $default = $sellerUser->addresses->firstWhere('is_default', true)
                ?? $sellerUser->addresses->first();
            $addrCity = trim((string) ($default?->city ?? ''));
            if ($addrCity !== '') {
                return $addrCity;
            }
        }

        return '';
    }

    public static function storeDisplayName(Product $product): string
    {
        $store = $product->relationLoaded('store') ? $product->store : $product->store;
        if ($store?->name) {
            return (string) $store->name;
        }

        return (string) ($product->sellerUser?->name ?? 'Seller');
    }
}
