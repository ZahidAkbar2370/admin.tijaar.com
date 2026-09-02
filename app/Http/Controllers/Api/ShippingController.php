<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Product;
use App\Services\PkCourierShippingService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Public estimate - guest checkout preview with cart line items.
     */
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'country' => 'nullable|string|max:100',
            'market' => 'nullable|string|in:PK,AE',
            'city' => 'nullable|string|max:100',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'courier_provider' => 'nullable|string|in:leopards,tcs',
        ]);

        $country = $request->country ?? 'Pakistan';
        $market = in_array(strtoupper($country), ['UAE', 'AE', 'UNITED ARAB EMIRATES']) ? 'AE' : 'PK';

        if ($market === 'AE') {
            $result = ShippingService::calculate((float) $request->subtotal, 0, $country, $market);

            return response()->json([
                'success' => true,
                'shipping' => [
                    'cost' => $result['cost'],
                    'total_cost' => $result['cost'],
                    'options' => $result['options'],
                    'stores' => [],
                    'rates_available' => true,
                ],
            ]);
        }

        $address = new Address([
            'city' => $request->city ?? '',
            'country' => $country,
        ]);

        $cartLines = $this->buildCartLines($request->input('items', []));
        $result = PkCourierShippingService::calculateForItems(
            $cartLines,
            $address,
            $market,
            $request->input('courier_provider')
        );

        return response()->json([
            'success' => true,
            'shipping' => $result,
        ]);
    }

    /**
     * Calculate shipping for authenticated checkout (cart + selected address).
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'address_id' => 'nullable|exists:addresses,id',
            'country' => 'nullable|string|max:100',
            'market' => 'nullable|string|in:PK,AE',
            'subtotal' => 'nullable|numeric|min:0',
            'courier_provider' => 'nullable|string|in:leopards,tcs',
        ]);

        $user = $request->user();
        $cart = $user ? Cart::getOrCreate($user->id) : null;

        $country = $request->country ?? 'Pakistan';
        $market = $request->market ?? 'PK';
        $address = null;

        if ($request->address_id && $user) {
            $address = Address::where('user_id', $user->id)->find($request->address_id);
            if ($address) {
                $country = $address->country ?? $country;
                $market = in_array(strtoupper($address->country ?? ''), ['UAE', 'AE', 'UNITED ARAB EMIRATES']) ? 'AE' : 'PK';
            }
        }

        if ($market === 'AE') {
            $subtotal = (float) ($request->subtotal ?? 0);
            if ($cart) {
                $cart->load('items.product');
                $subtotal = $cart->items->sum(fn ($i) => (float) $i->price * $i->quantity);
            }
            $result = ShippingService::calculate($subtotal, 0, $country, $market);

            return response()->json([
                'success' => true,
                'shipping' => [
                    'cost' => $result['cost'],
                    'total_cost' => $result['cost'],
                    'options' => $result['options'],
                    'zone_name' => $result['zone']?->name,
                    'stores' => [],
                    'rates_available' => true,
                ],
            ]);
        }

        if ($cart) {
            $cart->load('items.product.store', 'items.product.sellerUser.addresses');
            $result = PkCourierShippingService::calculateForItems(
                $cart->items,
                $address,
                'PK',
                $request->input('courier_provider')
            );

            return response()->json([
                'success' => true,
                'shipping' => $result,
            ]);
        }

        return response()->json([
            'success' => true,
            'shipping' => [
                'cost' => 0,
                'total_cost' => 0,
                'stores' => [],
                'rates_available' => false,
                'unavailable_message' => 'Cart not found.',
            ],
        ]);
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @return list<object{quantity: int, price: float, product: Product}>
     */
    protected function buildCartLines(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
        $products = Product::query()
            ->with(['store', 'sellerUser'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($items as $row) {
            $product = $products->get((int) $row['product_id']);
            if (!$product) {
                continue;
            }
            $lines[] = (object) [
                'quantity' => (int) $row['quantity'],
                'price' => (float) $product->price,
                'product' => $product,
            ];
        }

        return $lines;
    }
}
