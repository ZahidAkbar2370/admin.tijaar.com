<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $q = \Illuminate\Support\Str::limit($request->get('q', ''), 255, '');
        if (strlen($q) < 1) {
            return response()->json(['success' => true, 'products' => [], 'categories' => []]);
        }

        $products = Product::published()
            ->fromActiveSellers()
            ->inStock()
            ->with(['media', 'category'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
            });

        $now = now()->toDateTimeString();
        $products = $products
            ->orderByRaw(
                "CASE
                    WHEN EXISTS (
                        SELECT 1 FROM promotions
                        INNER JOIN promotion_packages ON promotion_packages.id = promotions.promotion_package_id
                        WHERE promotions.product_id = products.id
                          AND promotions.status = 'active'
                          AND promotions.starts_at <= ?
                          AND promotions.ends_at >= ?
                          AND promotion_packages.type IN ('featured_product', 'hot_sale')
                    ) THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM stores
                        INNER JOIN sellers ON sellers.id = stores.seller_id
                        WHERE stores.id = products.store_id
                          AND sellers.status = 'approved'
                          AND sellers.kyc_status = 'verified'
                    ) THEN 1
                    WHEN EXISTS (
                        SELECT 1 FROM wallet_transactions
                        WHERE wallet_transactions.reference_type = 'product'
                          AND wallet_transactions.reference_id = products.id
                          AND wallet_transactions.type = 'listing_fee'
                    ) THEN 2
                    ELSE 3
                END ASC",
                [$now, $now]
            )
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'price' => (float) $p->price,
                'image' => $p->getMainImageUrl(),
                'category' => $p->category?->name,
            ]);

        $categories = \App\Models\Category::active()
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    private function buildVariantOptions(Product $p): array
    {
        $variants = $p->relationLoaded('variants') ? $p->variants : $p->variants()->get();
        if ($variants->isEmpty()) {
            return [];
        }
        $opts = [];
        foreach ($variants as $v) {
            $attrs = $v->attributes ?? [];
            foreach ($attrs as $key => $val) {
                $k = ucfirst(strtolower($key));
                if (!isset($opts[$k])) {
                    $opts[$k] = [];
                }
                if (!in_array($val, $opts[$k], true)) {
                    $opts[$k][] = $val;
                }
            }
        }
        return $opts;
    }

    public function featured(): JsonResponse
    {
        $products = Product::with(['media', 'category', 'store', 'sellerUser', 'variants'])
            ->published()
            ->where('is_featured', true)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $formatted = $products->map(function ($p) {
            $store = $p->store;
            $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';
            return [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'title' => $p->name,
                'price' => (float) $p->price,
                'originalPrice' => $p->compare_at_price ? (float) $p->compare_at_price : null,
                'image' => $p->getMainImageUrl(),
                'vendor' => $vendor,
                'vendor_slug' => $store?->slug,
                'category' => $p->category?->name,
                'categorySlug' => $p->category?->slug,
                'quantity' => (int) $p->quantity,
                'track_inventory' => $p->track_inventory ?? true,
                'variant_options' => $this->buildVariantOptions($p),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $formatted,
        ]);
    }

    public function trending(): JsonResponse
    {
        $products = Product::with(['media', 'category', 'store', 'sellerUser', 'variants'])
            ->published()
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $formatted = $products->map(function ($p) {
            $store = $p->store;
            $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';
            return [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'title' => $p->name,
                'price' => (float) $p->price,
                'originalPrice' => $p->compare_at_price ? (float) $p->compare_at_price : null,
                'image' => $p->getMainImageUrl(),
                'vendor' => $vendor,
                'vendor_slug' => $store?->slug,
                'category' => $p->category?->name,
                'categorySlug' => $p->category?->slug,
                'quantity' => (int) $p->quantity,
                'track_inventory' => $p->track_inventory ?? true,
                'variant_options' => $this->buildVariantOptions($p),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $formatted,
        ]);
    }

    public function deals(): JsonResponse
    {
        // Only products from active (non-suspended, non-banned) sellers; exclude when admin has suspended/banned the seller
        $products = Product::with(['media', 'category', 'store', 'sellerUser', 'variants'])
            ->published()
            ->fromActiveSellers()
            ->whereNotNull('flash_deal_discount_value')
            ->where('flash_deal_discount_value', '>', 0)
            ->where(function ($q) {
                $q->whereNull('flash_deal_ends_at')
                    ->orWhere('flash_deal_ends_at', '>', now());
            })
            ->orderByDesc('flash_deal_ends_at')
            ->orderByDesc('flash_deal_discount_value')
            ->limit(12)
            ->get();

        $formatted = $products->map(function ($p) {
            $store = $p->store;
            $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';
            $price = (float) $p->price;
            $discountType = $p->flash_deal_discount_type ?? 'percentage';
            $discountValue = (float) ($p->flash_deal_discount_value ?? 0);
            $originalPrice = null;
            $discountPercent = 0;
            if ($discountType === 'percentage' && $discountValue > 0) {
                $discountPercent = min(100, round($discountValue));
                $originalPrice = $discountValue < 100 ? $price / (1 - $discountValue / 100) : ($p->compare_at_price ? (float) $p->compare_at_price : null);
            } elseif ($discountType === 'fixed' && $discountValue > 0) {
                $originalPrice = $price + $discountValue;
                $discountPercent = $originalPrice > 0 ? round((1 - $price / $originalPrice) * 100) : 0;
            }
            if ($originalPrice === null && $p->compare_at_price) {
                $originalPrice = (float) $p->compare_at_price;
                $discountPercent = $originalPrice > 0 ? round((1 - $price / $originalPrice) * 100) : $discountPercent;
            }
            return [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'title' => $p->name,
                'price' => $price,
                'originalPrice' => $originalPrice,
                'image' => $p->getMainImageUrl(),
                'vendor' => $vendor,
                'vendor_slug' => $store?->slug,
                'category' => $p->category?->name,
                'categorySlug' => $p->category?->slug,
                'quantity' => (int) $p->quantity,
                'track_inventory' => $p->track_inventory ?? true,
                'variant_options' => $this->buildVariantOptions($p),
                'discount_percent' => $discountPercent,
                'flash_deal_discount_type' => $discountType,
                'flash_deal_discount_value' => $discountValue,
                'flash_deal_ends_at' => $p->flash_deal_ends_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $formatted,
        ]);
    }
}
