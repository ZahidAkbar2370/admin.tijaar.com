<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    /**
     * For variable products, set product.quantity to the sum of all variant quantities.
     */
    private function syncProductQuantityFromVariants(Product $product): void
    {
        if (($product->product_type ?? 'simple') !== 'variable') {
            return;
        }
        $product->update(['quantity' => $product->variants()->sum('quantity')]);
    }

    private function formatVariant(ProductVariant $v): array
    {
        $data = $v->toArray();
        $data['attributes'] = $v->attributes ?? [];
        $data['image_url'] = $v->image_path ? \App\Support\UploadHelper::url($v->image_path) : null;
        $paths = $v->image_paths ?? [];
        if (!is_array($paths)) {
            $paths = [];
        }
        if ($v->image_path && !in_array($v->image_path, $paths, true)) {
            array_unshift($paths, $v->image_path);
        }
        $data['image_urls'] = array_values(array_map(fn ($p) => \App\Support\UploadHelper::url($p), array_filter($paths)));
        return $data;
    }

    public function index(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $variants = $product->variants->map(fn ($v) => $this->formatVariant($v));
        return response()->json(['success' => true, 'variants' => $variants->values()->all()]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'attributes' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $attrs = $request->input('attributes');
        if (!is_array($attrs)) {
            $attrs = [];
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $request->sku ?: 'VAR-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'name' => $request->name,
            'attributes' => $attrs,
            'price' => $request->price,
            'compare_at_price' => $request->compare_at_price,
            'quantity' => $request->quantity,
        ]);

        $imagePaths = [];
        if ($request->hasFile('image')) {
            $path = \App\Support\UploadHelper::storePublic($request->file('image'), 'products/' . $product->id . '/variants');
            $imagePaths[] = $path;
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id . '/variants');
            }
        }
        if (!empty($imagePaths)) {
            $variant->update([
                'image_path' => $imagePaths[0],
                'image_paths' => $imagePaths,
            ]);
        }

        $this->syncProductQuantityFromVariants($product);

        return response()->json([
            'success' => true,
            'message' => 'Variant added.',
            'variant' => $this->formatVariant($variant->fresh()),
        ], 201);
    }

    public function storeBulk(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $request->validate([
            'variants' => 'required|array|min:1',
            'variants.*.attributes' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
            'variants.*.name' => 'nullable|string|max:255',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.compare_at_price' => 'nullable|numeric|min:0',
        ]);

        $created = [];
        foreach ($request->variants as $row) {
            $attrs = $row['attributes'] ?? [];
            $name = $row['name'] ?? null;
            if (!$name && !empty($attrs)) {
                $name = implode(' / ', array_map(fn ($k, $v) => $v, array_keys($attrs), $attrs));
            }
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $row['sku'] ?? 'VAR-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'name' => $name,
                'attributes' => $attrs,
                'price' => $row['price'],
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'quantity' => (int) ($row['quantity'] ?? 0),
            ]);
            $created[] = $this->formatVariant($variant);
        }

        $this->syncProductQuantityFromVariants($product->fresh());

        return response()->json([
            'success' => true,
            'message' => count($created) . ' variant(s) added.',
            'variants' => $created,
        ], 201);
    }

    public function update(Request $request, int $productId, int $variantId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $variant = ProductVariant::where('product_id', $product->id)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'attributes' => 'nullable|array',
            'price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $updates = $request->only(['name', 'sku', 'attributes', 'price', 'compare_at_price', 'quantity']);
        if (isset($updates['attributes']) && !is_array($updates['attributes'])) {
            $updates['attributes'] = [];
        }
        $variant->update(array_filter($updates, fn ($v) => $v !== null));

        $imagePaths = $variant->image_paths ?? [];
        if (!is_array($imagePaths)) {
            $imagePaths = [];
        }
        if ($variant->image_path && !in_array($variant->image_path, $imagePaths, true)) {
            array_unshift($imagePaths, $variant->image_path);
        }
        $hasNewImages = $request->hasFile('image') || $request->hasFile('images');
        if ($hasNewImages) {
            $newPaths = [];
            if ($request->hasFile('image')) {
                $newPaths[] = \App\Support\UploadHelper::storePublic($request->file('image'), 'products/' . $product->id . '/variants');
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $newPaths[] = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id . '/variants');
                }
            }
            $imagePaths = array_values(array_filter(array_merge($imagePaths, $newPaths)));
            $variant->update([
                'image_path' => $imagePaths[0] ?? null,
                'image_paths' => $imagePaths,
            ]);
        }

        $this->syncProductQuantityFromVariants($product);

        return response()->json([
            'success' => true,
            'message' => 'Variant updated.',
            'variant' => $this->formatVariant($variant->fresh()),
        ]);
    }

    public function destroy(Request $request, int $productId, int $variantId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $variant = ProductVariant::where('product_id', $product->id)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
        }

        $variant->delete();
        $this->syncProductQuantityFromVariants($product);
        return response()->json(['success' => true, 'message' => 'Variant deleted.']);
    }
}
