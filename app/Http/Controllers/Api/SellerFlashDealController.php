<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Support\UploadHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SellerFlashDealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $deals = FlashDeal::where('store_id', $store->id)
            ->with(['products' => fn ($q) => $q->with(['media', 'variants'])->select('products.id', 'products.name', 'products.slug', 'products.price', 'products.thumbnail_path')])
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

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'product_ids' => 'required_without:product_selections|array',
            'product_ids.*' => 'integer|exists:products,id',
            'product_selections' => 'required_without:product_ids|array',
            'product_selections.*.product_id' => 'required|integer|exists:products,id',
            'product_selections.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'discount_type' => 'required|string|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'ends_at' => 'nullable|date',
            'image' => 'nullable|image|max:5120',
            'image_alt' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $selections = $request->product_selections;
        if (!$selections && $request->product_ids) {
            $selections = array_map(fn ($id) => ['product_id' => $id, 'variant_id' => null], array_values($request->product_ids));
        }
        if (empty($selections)) {
            return response()->json(['success' => false, 'message' => 'Select at least one product'], 422);
        }

        $productIds = array_unique(array_column($selections, 'product_id'));
        $owned = Product::where('store_id', $store->id)->whereIn('id', $productIds)->pluck('id')->toArray();
        if (count($owned) !== count($productIds)) {
            return response()->json(['success' => false, 'message' => 'Some products not found or not yours'], 422);
        }

        $discountValue = $request->discount_type === 'percentage'
            ? min(100, (float) $request->discount_value)
            : (float) $request->discount_value;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = UploadHelper::storePublic($request->file('image'), 'flash-deals');
        }

        $deal = FlashDeal::create([
            'store_id' => $store->id,
            'name' => $request->name,
            'image_path' => $imagePath,
            'image_alt' => $request->input('image_alt'),
            'discount_type' => $request->discount_type,
            'discount_value' => $discountValue,
            'ends_at' => $request->filled('ends_at') ? $request->ends_at : null,
            'is_active' => true,
        ]);

        foreach (array_values($selections) as $i => $sel) {
            $variantId = isset($sel['variant_id']) && $sel['variant_id'] ? (int) $sel['variant_id'] : null;
            $deal->products()->attach($sel['product_id'], ['sort_order' => $i, 'variant_id' => $variantId]);
        }

        $deal->load(['products', 'products.variants']);
        Cache::forget('api.home');
        return response()->json([
            'success' => true,
            'message' => 'Flash Deal created',
            'flash_deal' => $this->formatDeal($deal),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $deal = FlashDeal::where('store_id', $store->id)->with(['products.media', 'products.variants'])->find($id);
        if (!$deal) {
            return response()->json(['success' => false, 'message' => 'Flash Deal not found'], 404);
        }

        return response()->json([
            'success' => true,
            'flash_deal' => $this->formatDeal($deal),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'product_ids' => 'sometimes|array',
            'product_ids.*' => 'integer|exists:products,id',
            'product_selections' => 'sometimes|array',
            'product_selections.*.product_id' => 'required_with:product_selections|integer|exists:products,id',
            'product_selections.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'ends_at' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:5120',
            'image_alt' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $deal = FlashDeal::where('store_id', $store->id)->find($id);
        if (!$deal) {
            return response()->json(['success' => false, 'message' => 'Flash Deal not found'], 404);
        }

        $data = $request->only(['name', 'discount_type', 'discount_value', 'ends_at', 'is_active', 'image_alt']);
        if ($request->has('discount_value')) {
            $data['discount_value'] = $request->discount_type === 'percentage'
                ? min(100, (float) $request->discount_value)
                : (float) $request->discount_value;
        }
        if ($request->filled('ends_at')) {
            $data['ends_at'] = $request->ends_at;
        } elseif (array_key_exists('ends_at', $request->all())) {
            $data['ends_at'] = null;
        }

        if ($request->hasFile('image')) {
            if ($deal->image_path) {
                UploadHelper::deleteAny($deal->image_path);
            }
            $data['image_path'] = UploadHelper::storePublic($request->file('image'), 'flash-deals');
        }

        $deal->update($data);

        if ($request->has('product_ids') || $request->has('product_selections')) {
            $selections = $request->product_selections;
            if (!$selections && $request->product_ids) {
                $selections = array_map(fn ($id) => ['product_id' => $id, 'variant_id' => null], array_values($request->product_ids));
            }
            if (!empty($selections)) {
                $productIds = array_unique(array_column($selections, 'product_id'));
                $owned = Product::where('store_id', $store->id)->whereIn('id', $productIds)->pluck('id')->toArray();
                if (count($owned) === count($productIds)) {
                    $deal->products()->sync([]);
                    foreach (array_values($selections) as $i => $sel) {
                        $variantId = isset($sel['variant_id']) && $sel['variant_id'] ? (int) $sel['variant_id'] : null;
                        $deal->products()->attach($sel['product_id'], ['sort_order' => $i, 'variant_id' => $variantId]);
                    }
                }
            }
        }

        $deal->load('products');
        Cache::forget('api.home');
        return response()->json([
            'success' => true,
            'message' => 'Flash Deal updated',
            'flash_deal' => $this->formatDeal($deal),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $deal = FlashDeal::where('store_id', $store->id)->find($id);
        if (!$deal) {
            return response()->json(['success' => false, 'message' => 'Flash Deal not found'], 404);
        }

        if ($deal->image_path) {
            UploadHelper::deleteAny($deal->image_path);
        }
        $deal->delete();
        Cache::forget('api.home');

        return response()->json([
            'success' => true,
            'message' => 'Flash Deal deleted',
        ]);
    }

    private function formatDeal(FlashDeal $deal): array
    {
        $products = $deal->relationLoaded('products') ? $deal->products : $deal->products()->with('variants')->get();
        $productList = $products->map(function ($p) {
            $pivot = $p->pivot;
            $variantId = $pivot && $pivot->variant_id ? (int) $pivot->variant_id : null;
            $variant = $variantId && $p->relationLoaded('variants') ? $p->variants->firstWhere('id', $variantId) : null;
            $price = $variant ? (float) $variant->price : (float) $p->price;
            $image = $variant && $variant->image_path
                ? \App\Support\UploadHelper::url($variant->image_path)
                : $p->getMainImageUrl();
            $attributes = $variant && is_array($variant->attributes) ? $variant->attributes : [];
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $price,
                'image' => $image,
                'variant_id' => $variantId,
                'variant_attributes' => $attributes,
            ];
        })->toArray();

        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'slug' => $deal->slug,
            'image_path' => $deal->image_path,
            'image_url' => $deal->image_url,
            'discount_type' => $deal->discount_type,
            'discount_value' => (float) $deal->discount_value,
            'ends_at' => $deal->ends_at?->toIso8601String(),
            'is_active' => $deal->is_active,
            'products' => $productList,
            'store' => $deal->relationLoaded('store') ? [
                'id' => $deal->store->id,
                'name' => $deal->store->name,
                'slug' => $deal->store->slug,
            ] : null,
        ];
    }
}
