<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashDealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $deals = FlashDeal::query()
            ->active()
            ->notExpired()
            ->with(['store:id,name,slug', 'products' => fn ($q) => $q->where('products.status', 'published')->with(['media', 'variants'])])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        $list = $deals->map(function ($deal) {
            return $this->formatDeal($deal);
        });

        return response()->json([
            'success' => true,
            'flash_deals' => $list,
        ]);
    }

    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $deal = FlashDeal::query()
            ->active()
            ->notExpired()
            ->where(fn ($q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->with(['store:id,name,slug', 'products' => fn ($q) => $q->where('products.status', 'published')->with(['media', 'variants'])])
            ->first();

        if (!$deal) {
            return response()->json(['success' => false, 'message' => 'Flash Deal not found'], 404);
        }

        return response()->json([
            'success' => true,
            'flash_deal' => $this->formatDeal($deal),
        ]);
    }

    private function formatDeal(FlashDeal $deal): array
    {
        $products = $deal->relationLoaded('products') ? $deal->products : $deal->products()->where('products.status', 'published')->with('variants')->get();
        $productList = $products->map(function ($p) {
            $pivot = $p->pivot;
            $variantId = $pivot && $pivot->variant_id ? (int) $pivot->variant_id : null;
            $variant = $variantId && $p->relationLoaded('variants') ? $p->variants->firstWhere('id', $variantId) : null;
            $price = $variant ? (float) $variant->price : (float) $p->price;
            $compareAt = $variant && $variant->compare_at_price ? (float) $variant->compare_at_price : ($p->compare_at_price ? (float) $p->compare_at_price : null);
            $image = $variant && $variant->image_path ? \App\Support\UploadHelper::url($variant->image_path) : $p->getMainImageUrl();
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $price,
                'compare_at_price' => $compareAt,
                'image' => $image,
                'variant_id' => $variantId,
                'variant_attributes' => $variant && is_array($variant->attributes) ? $variant->attributes : [],
            ];
        })->toArray();

        $totalOriginal = collect($productList)->sum('price');
        $discountValue = (float) $deal->discount_value;
        $discountType = $deal->discount_type ?? 'percentage';
        $discountAmount = $discountType === 'percentage'
            ? $totalOriginal * ($discountValue / 100)
            : min($discountValue, $totalOriginal);
        $dealPrice = max(0, $totalOriginal - $discountAmount);

        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'slug' => $deal->slug,
            'image_path' => $deal->image_path,
            'image_url' => $deal->image_url,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'ends_at' => $deal->ends_at?->toIso8601String(),
            'is_active' => $deal->is_active,
            'products' => $productList,
            'total_original_price' => round($totalOriginal, 2),
            'deal_price' => round($dealPrice, 2),
            'store' => $deal->relationLoaded('store') && $deal->store ? [
                'id' => $deal->store->id,
                'name' => $deal->store->name,
                'slug' => $deal->store->slug,
            ] : null,
        ];
    }
}
