<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ListingPendingApprovalMail;
use App\Support\ProductSeoHelper;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $query = Product::withTrashed()
            ->where('store_id', $store->id)
            ->with(['category', 'brand', 'store', 'media', 'variants'])
            ->withCount('wishlists');

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'removed') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status)->whereNull('deleted_at');
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->boolean('top_seller')) {
            $query->withSum(['orderItems as total_sold' => fn ($q) => $q->where('seller_type', 'business')], 'quantity')
                ->orderByDesc('total_sold');
        }
        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('is_hot')) {
            $query->where('is_hot', true);
        }

        if (!$request->boolean('top_seller')) {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $paginated = $query->paginate($perPage);

        $baseUrl = rtrim(config('app.url') ?: $request->getSchemeAndHttpHost(), '/');
        foreach ($paginated->getCollection() as $p) {
            $path = $p->thumbnail_path
                ? $p->thumbnail_path
                : ($p->media->first() ? $p->media->first()->path : null);
            if ($path) {
                $relative = ltrim($path, '/');
                $p->thumbnail_url = str_starts_with($relative, 'upload/')
                    ? $baseUrl . '/' . $relative
                    : $baseUrl . '/storage/' . $relative;
            } else {
                $p->thumbnail_url = null;
            }
            // For variable products, main quantity = sum of all variant quantities
            $p->quantity = $p->getEffectiveQuantity();
            $p->wishlist_count = (int) ($p->wishlists_count ?? 0);
            $p->impressions_count = (int) ($p->impressions_count ?? 0);
            $p->clicks_count = (int) ($p->clicks_count ?? 0);
            $p->shares_count = (int) ($p->shares_count ?? 0);
            $p->is_removed = $p->trashed() || $p->status === 'removed';
        }

        return response()->json([
            'success' => true,
            'products' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
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

        $product = Product::where('store_id', $store->id)
            ->with(['category', 'brand', 'media', 'documents', 'variants'])
            ->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product->thumbnail_url = $product->thumbnail_path
            ? (\App\Support\UploadHelper::url($product->thumbnail_path))
            : ($product->media->first() ? \App\Support\UploadHelper::url($product->media->first()->path) : null);

        // Attach full image_url to each media so the UI can display gallery images correctly
        $product->setRelation('media', $product->media->map(function ($m) {
            $m->image_url = \App\Support\UploadHelper::url($m->path);
            return $m;
        }));

        // For variable products, quantity shown is the sum of all variant quantities
        if (($product->product_type ?? 'simple') === 'variable' && $product->variants->isNotEmpty()) {
            $product->quantity = $product->variants->sum('quantity');
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function createProduct(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Create a store first before adding products.'], 400);
        }
        $seller = $user->seller;
        if ($seller->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Your store must be approved by admin before you can add products. Please wait for approval.',
            ], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'condition' => 'required|in:new,used,refurbished',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'thumbnail_alt' => 'nullable|string|max:255',
            'image_alts' => 'nullable|array',
            'image_alts.*' => 'nullable|string|max:255',
            'video_url' => 'nullable|url|max:500',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'document_labels.*' => 'nullable|string|max:100',
            'is_featured' => 'nullable|boolean',
            'is_hot' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published',
            'product_type' => 'nullable|in:simple,variable',
            'weight_kg' => 'nullable|numeric|min:0.01|max:500',
            'shipping_mode' => 'nullable|string|in:customer_pays,free_shipping,included_in_price',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shippingMode = $request->input('shipping_mode', 'customer_pays');
        if ($shippingMode === 'customer_pays' && !$request->filled('shipping_cost_cached')) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping price is required when the customer pays shipping.',
            ], 422);
        }

        // Check featured/hot eligibility (requires active promotion)
        $isFeatured = (bool) $request->input('is_featured');
        $isHot = (bool) $request->input('is_hot');
        if ($isFeatured && !$this->checkPromotionEligibility($user->id, $store->id, 'featured_product')) {
            return response()->json([
                'success' => false,
                'message' => 'You need to purchase a Featured promotion package. Visit Promote page to buy a package.',
            ], 422);
        }
        if ($isHot && !$this->checkPromotionEligibility($user->id, $store->id, 'hot_sale')) {
            return response()->json([
                'success' => false,
                'message' => 'You need to purchase a Hot promotion package. Visit Promote page to buy a package.',
            ], 422);
        }

        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        // Determine product status based on seller's choice and admin settings
        $requestedStatus = $request->input('status', 'published');
        $approvalRequired = \App\Models\Setting::get('product_approval_required', '0') === '1';
        
        // If seller chose draft, keep it as draft
        // If seller chose published, check if approval is required
        if ($requestedStatus === 'draft') {
            $productStatus = 'draft';
        } else {
            $productStatus = $approvalRequired ? 'pending' : 'published';
        }

        $productType = $request->input('product_type', 'simple');
        $meta = ProductSeoHelper::resolve($request->all());
        $product = Product::create([
            'seller_type' => 'business',
            'seller_id' => $user->id,
            'store_id' => $store->id,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'sku' => $request->sku ?: 'STR-' . strtoupper(Str::random(8)),
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'compare_at_price' => $request->compare_at_price ?: null,
            'quantity' => $productType === 'variable' ? max(0, (int) $request->quantity) : $request->quantity,
            'condition' => $request->condition,
            'status' => $productStatus,
            'product_type' => $productType,
            'video_url' => $request->video_url ?: null,
            'is_featured' => $isFeatured,
            'is_hot' => $isHot,
            'weight_kg' => $request->input('weight_kg', 0.5),
            'shipping_mode' => $shippingMode,
            'shipping_cost_cached' => $shippingMode === 'customer_pays'
                ? (float) $request->input('shipping_cost_cached', 0)
                : 0,
            'length_cm' => $request->input('length_cm') ?: null,
            'width_cm' => $request->input('width_cm') ?: null,
            'height_cm' => $request->input('height_cm') ?: null,
            'meta_title' => $meta['meta_title'],
            'meta_description' => $meta['meta_description'],
            'meta_keywords' => $meta['meta_keywords'],
        ]);

        // Issue 18: Main image is always from thumbnail field; gallery images never override it
        $thumbnailPath = null;
        $thumbnailFile = $request->file('thumbnail');
        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $thumbnailPath = \App\Support\UploadHelper::storePublic($thumbnailFile, 'products/' . $product->id);
            $product->update([
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_alt' => $request->input('thumbnail_alt'),
            ]);
        } elseif ($request->filled('thumbnail_alt')) {
            $product->update(['thumbnail_alt' => $request->input('thumbnail_alt')]);
        }

        $images = [];
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            if (! is_array($images)) {
                $images = $images ? [$images] : [];
            }
        }
        foreach ($images as $idx => $file) {
            if (! $file || ! (method_exists($file, 'isValid') ? $file->isValid() : true)) {
                continue;
            }
            $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id);
            $imageAlts = $request->input('image_alts', []);
            ProductMedia::create([
                'product_id' => $product->id,
                'type' => 'image',
                'path' => $path,
                'alt_text' => $imageAlts[$idx] ?? null,
                'sort_order' => $idx,
                'is_thumbnail' => false,
            ]);
        }
        // When no thumbnail was uploaded, use first gallery image as main image
        if (! $thumbnailPath) {
            $firstMedia = $product->media()->orderBy('sort_order')->first();
            if ($firstMedia) {
                $product->update(['thumbnail_path' => $firstMedia->path]);
                $firstMedia->update(['is_thumbnail' => true]);
            }
        }

        if ($request->hasFile('documents')) {
            $labels = $request->input('document_labels', []);
            foreach ($request->file('documents') as $idx => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id . '/docs');
                ProductDocument::create([
                    'product_id' => $product->id,
                    'type' => 'manual',
                    'label' => $labels[$idx] ?? $file->getClientOriginalName(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => $idx,
                ]);
            }
        }

        // Generate appropriate success message
        if ($productStatus === 'draft') {
            $message = 'Product saved as draft. Publish it when ready.';
        } elseif ($approvalRequired) {
            $message = 'Product created and submitted for admin approval.';
            try {
                Mail::to($user->email)->send(new ListingPendingApprovalMail(
                    $user->name ?: 'Seller',
                    $product->name,
                ));
            } catch (\Throwable $e) {
                // Log but do not fail the request
                report($e);
            }
        } else {
            $message = 'Product created and published!';
        }

        $product->refresh(['store']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'product' => $product->fresh(['category', 'brand', 'media', 'documents']),
            'requires_approval' => $approvalRequired && $productStatus !== 'draft',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $rules = [
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'sometimes|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'condition' => 'sometimes|in:new,used,refurbished',
            'status' => 'sometimes|in:draft,published,unpublished',
            'video_url' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
            'is_hot' => 'nullable|boolean',
            'product_type' => 'nullable|in:simple,variable',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'thumbnail_alt' => 'nullable|string|max:255',
            'image_alts' => 'nullable|array',
            'image_alts.*' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'weight_kg' => 'nullable|numeric|min:0.01|max:500',
            'shipping_mode' => 'nullable|string|in:customer_pays,free_shipping,included_in_price',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $isFeatured = $request->has('is_featured') ? (bool) $request->is_featured : $product->is_featured;
        $isHot = $request->has('is_hot') ? (bool) $request->is_hot : $product->is_hot;
        if ($isFeatured) {
            if (!$this->checkPromotionEligibility($user->id, $store->id, 'featured_product')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase a Featured promotion package to use this badge. Visit Promote page.',
                ], 422);
            }
        }
        if ($isHot) {
            if (!$this->checkPromotionEligibility($user->id, $store->id, 'hot_sale')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase a Hot promotion package to use this badge. Visit Promote page.',
                ], 422);
            }
        }

        $updateData = $request->only([
            'name', 'sku', 'description', 'short_description', 'category_id', 'brand_id',
            'price', 'compare_at_price', 'condition', 'video_url', 'product_type',
            'weight_kg', 'shipping_mode', 'shipping_cost_cached', 'length_cm', 'width_cm', 'height_cm',
        ]);
        if ($request->has('shipping_mode')) {
            $mode = $request->input('shipping_mode');
            if ($mode === 'customer_pays') {
                if (!$request->filled('shipping_cost_cached') && $product->shipping_cost_cached === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Shipping price is required when the customer pays shipping.',
                    ], 422);
                }
                if ($request->filled('shipping_cost_cached')) {
                    $updateData['shipping_cost_cached'] = (float) $request->input('shipping_cost_cached');
                }
            } else {
                $updateData['shipping_cost_cached'] = 0;
            }
        }
        if (($product->product_type ?? 'simple') !== 'variable' && $request->has('quantity')) {
            $updateData['quantity'] = $request->quantity;
        }
        if ($request->has('status')) {
            $requestedStatus = $request->input('status');
            if ($requestedStatus === 'draft') {
                $updateData['status'] = 'draft';
            } elseif ($requestedStatus === 'unpublished') {
                $updateData['status'] = 'unpublished';
            } else {
                // published → pending when admin approval is required (same as create)
                $approvalRequired = \App\Models\Setting::get('product_approval_required', '0') === '1';
                $updateData['status'] = $approvalRequired ? 'pending' : 'published';
            }
        }
        $updateData['is_featured'] = $isFeatured;
        $updateData['is_hot'] = $isHot;
        if ($request->filled('name')) {
            $updateData['slug'] = Str::slug($request->name) . '-' . $product->id;
        }
        $meta = ProductSeoHelper::resolve(array_merge([
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
        ], $request->only(['name', 'short_description', 'description', 'meta_title', 'meta_description', 'meta_keywords'])));
        $updateData['meta_title'] = $meta['meta_title'];
        $updateData['meta_description'] = $meta['meta_description'];
        $updateData['meta_keywords'] = $meta['meta_keywords'];
        // Keep intentional falsy values (e.g. quantity 0); only drop nulls
        $product->update(array_filter($updateData, static fn ($v) => $v !== null));

        if ($request->has('quantity') && ($product->product_type ?? 'simple') !== 'variable') {
            \App\Models\ReservedStock::where('product_id', $product->id)
                ->where('expires_at', '<', now())
                ->delete();
        }

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail_path) {
                \App\Support\UploadHelper::deleteAny($product->thumbnail_path);
            }
            $path = \App\Support\UploadHelper::storePublic($request->file('thumbnail'), 'products/' . $product->id);
            $product->update([
                'thumbnail_path' => $path,
                'thumbnail_alt' => $request->input('thumbnail_alt', $product->thumbnail_alt),
            ]);
        } elseif ($request->filled('thumbnail_alt')) {
            $product->update(['thumbnail_alt' => $request->input('thumbnail_alt')]);
        }

        if ($request->hasFile('images')) {
            $product->media()->delete();
            $images = $request->file('images');
            if (!is_array($images)) {
                $images = $images ? [$images] : [];
            }
            foreach ($images as $idx => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id);
                $imageAlts = $request->input('image_alts', []);
                ProductMedia::create([
                    'product_id' => $product->id,
                    'type' => 'image',
                    'path' => $path,
                    'alt_text' => $imageAlts[$idx] ?? null,
                    'sort_order' => $idx,
                    'is_thumbnail' => false,
                ]);
            }
            if (!$product->thumbnail_path) {
                $firstMedia = $product->media()->orderBy('sort_order')->first();
                if ($firstMedia) {
                    $product->update(['thumbnail_path' => $firstMedia->path]);
                    $firstMedia->update(['is_thumbnail' => true]);
                }
            }
        }

        if ($request->hasFile('documents')) {
            $labels = $request->input('document_labels', []);
            foreach ($request->file('documents') as $idx => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id . '/docs');
                ProductDocument::create([
                    'product_id' => $product->id,
                    'type' => 'manual',
                    'label' => $labels[$idx] ?? $file->getClientOriginalName(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => $product->documents()->count() + $idx,
                ]);
            }
        }

        $product->refresh(['store']);
        \App\Services\StockAlertService::syncForProduct($product->fresh(['variants']));

        return response()->json([
            'success' => true,
            'message' => 'Product updated.',
            'product' => $product->fresh(['category', 'brand', 'media', 'documents']),
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

        $product = Product::where('store_id', $store->id)->find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Soft-hide from public; order history keeps product details. Seller can restore anytime.
        $product->status = 'removed';
        $product->save();
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product hidden from public. You can recover it anytime. Past orders are unaffected.',
        ]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::withTrashed()->where('store_id', $store->id)->find($id);
        if (!$product || !$product->trashed()) {
            return response()->json(['success' => false, 'message' => 'Removed product not found'], 404);
        }

        $product->restore();
        $product->status = 'draft';
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product recovered as draft. Publish when ready.',
            'product' => $product->fresh(['category', 'brand', 'media']),
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => true, 'rows' => [], 'columns' => []]);
        }

        $query = Product::where('store_id', $store->id)->with(['category', 'brand', 'media', 'variants']);
        $this->applyFilters($query, $request);
        $products = $query->orderBy('created_at', 'desc')->get();

        $columns = [
            'id', 'name', 'sku', 'category', 'brand', 'price', 'compare_at_price',
            'quantity', 'condition', 'status', 'is_featured', 'is_hot', 'created_at',
            'description', 'short_description',
            'thumbnail_url', 'image_urls', 'variants',
        ];
        $rows = $products->map(function ($p) {
            $thumbnailUrl = $p->thumbnail_path
                ? \App\Support\UploadHelper::url($p->thumbnail_path)
                : ($p->media->first() ? \App\Support\UploadHelper::url($p->media->first()->path) : '');
            $imageUrls = $p->media->map(fn ($m) => \App\Support\UploadHelper::url($m->path))->values()->all();
            $variantsData = $p->variants->map(fn ($v) => [
                'sku' => $v->sku,
                'name' => $v->name,
                'attributes' => $v->attributes ?? [],
                'price' => (float) $v->price,
                'compare_at_price' => $v->compare_at_price ? (float) $v->compare_at_price : null,
                'quantity' => (int) $v->quantity,
                'image_url' => $v->image_path ? \App\Support\UploadHelper::url($v->image_path) : null,
            ])->values()->all();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category?->name ?? '',
                'brand' => $p->brand?->name ?? '',
                'price' => $p->price,
                'compare_at_price' => $p->compare_at_price ?? '',
                'quantity' => $p->getEffectiveQuantity(),
                'condition' => $p->condition,
                'status' => $p->status,
                'is_featured' => $p->is_featured ? 'Yes' : 'No',
                'is_hot' => $p->is_hot ? 'Yes' : 'No',
                'created_at' => $p->created_at?->format('Y-m-d H:i') ?? '',
                'description' => $p->description ?? '',
                'short_description' => $p->short_description ?? '',
                'thumbnail_url' => $thumbnailUrl,
                'image_urls' => json_encode($imageUrls),
                'variants' => json_encode($variantsData),
            ];
        });

        return response()->json(['success' => true, 'rows' => $rows, 'columns' => $columns]);
    }

    public function import(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Create a store first.'], 400);
        }
        if ($user->seller->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Your store must be approved before importing products.'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rows' => 'required|array',
            'rows.*' => 'array',
            'mapping' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $mapping = $request->mapping;
        $rows = $request->rows;
        $created = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            try {
                $name = $this->getMappedValue($row, $mapping, 'name');
                if (empty($name)) continue;

                $categoryName = $this->getMappedValue($row, $mapping, 'category');
                $categoryId = \App\Models\Category::where('name', $categoryName)->value('id')
                    ?? \App\Models\Category::first()?->id;
                if (!$categoryId) {
                    $errors[] = "Row " . ($idx + 1) . ": No category found.";
                    continue;
                }

                $price = (float) ($this->getMappedValue($row, $mapping, 'price') ?: 0);
                $quantity = (int) ($this->getMappedValue($row, $mapping, 'quantity') ?: 0);

                $slug = Str::slug($name);
                $baseSlug = $slug;
                $i = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $i++;
                }

                $product = Product::create([
                    'seller_type' => 'business',
                    'seller_id' => $user->id,
                    'store_id' => $store->id,
                    'category_id' => $categoryId,
                    'brand_id' => null,
                    'sku' => $this->getMappedValue($row, $mapping, 'sku') ?: 'IMP-' . strtoupper(Str::random(6)),
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $this->getMappedValue($row, $mapping, 'description'),
                    'short_description' => $this->getMappedValue($row, $mapping, 'short_description'),
                    'price' => $price,
                    'compare_at_price' => $this->getMappedValue($row, $mapping, 'compare_at_price') ?: null,
                    'quantity' => $quantity,
                    'condition' => $this->getMappedValue($row, $mapping, 'condition') ?: 'new',
                    'status' => 'published',
                ]);

                // Thumbnail from URL (if mapped and valid)
                $thumbnailUrl = $this->getMappedValue($row, $mapping, 'thumbnail_url');
                if ($thumbnailUrl && filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
                    $thumbPath = $this->downloadImageToStorage($thumbnailUrl, 'products/' . $product->id);
                    if ($thumbPath) {
                        $product->update(['thumbnail_path' => $thumbPath]);
                    }
                }

                // Gallery images from image_urls JSON (e.g. ["url1","url2"])
                $imageUrlsRaw = $this->getMappedValue($row, $mapping, 'image_urls');
                if ($imageUrlsRaw) {
                    $imageUrls = json_decode($imageUrlsRaw, true);
                    if (is_array($imageUrls)) {
                        $sortOrder = 0;
                        foreach ($imageUrls as $imgUrl) {
                            if (is_string($imgUrl) && filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $path = $this->downloadImageToStorage($imgUrl, 'products/' . $product->id . '/gallery');
                                if ($path) {
                                    ProductMedia::create([
                                        'product_id' => $product->id,
                                        'type' => 'image',
                                        'path' => $path,
                                        'sort_order' => $sortOrder++,
                                        'is_thumbnail' => false,
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Variants from variants JSON
                $variantsRaw = $this->getMappedValue($row, $mapping, 'variants');
                if ($variantsRaw) {
                    $variantsData = json_decode($variantsRaw, true);
                    if (is_array($variantsData)) {
                        foreach ($variantsData as $v) {
                            $attrs = isset($v['attributes']) && is_array($v['attributes']) ? $v['attributes'] : [];
                            $variantImagePath = null;
                            if (!empty($v['image_url']) && filter_var($v['image_url'], FILTER_VALIDATE_URL)) {
                                $variantImagePath = $this->downloadImageToStorage($v['image_url'], 'products/' . $product->id . '/variants');
                            }
                            ProductVariant::create([
                                'product_id' => $product->id,
                                'sku' => $v['sku'] ?? 'VAR-' . strtoupper(Str::random(6)),
                                'name' => $v['name'] ?? null,
                                'attributes' => $attrs,
                                'price' => (float) ($v['price'] ?? $product->price),
                                'compare_at_price' => isset($v['compare_at_price']) ? (float) $v['compare_at_price'] : null,
                                'quantity' => (int) ($v['quantity'] ?? 0),
                                'image_path' => $variantImagePath,
                            ]);
                        }
                    }
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . ($idx + 1) . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Imported {$created} product(s).",
            'created' => $created,
            'errors' => $errors,
        ]);
    }

    public function promotionEligibility(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json([
                'success' => true,
                'featured_eligible' => false,
                'hot_eligible' => false,
                'message' => 'Create a store first.',
            ]);
        }

        $featuredEligible = $this->checkPromotionEligibility($user->id, $store->id, 'featured_product');
        $hotEligible = $this->checkPromotionEligibility($user->id, $store->id, 'hot_sale');

        return response()->json([
            'success' => true,
            'featured_eligible' => $featuredEligible,
            'hot_eligible' => $hotEligible,
        ]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        // Find the original product
        $original = Product::where('store_id', $store->id)
            ->with(['media', 'documents', 'variants'])
            ->find($id);

        if (!$original) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        try {
            // Generate unique slug for duplicate
            $slug = Str::slug($original->name . ' copy');
            $baseSlug = $slug;
            $i = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            // Generate unique SKU
            $sku = 'DUP-' . strtoupper(Str::random(8));
            while (Product::where('sku', $sku)->exists()) {
                $sku = 'DUP-' . strtoupper(Str::random(8));
            }

            // Create duplicate product with draft status
            $duplicate = Product::create([
                'seller_type' => $original->seller_type,
                'seller_id' => $original->seller_id,
                'store_id' => $original->store_id,
                'category_id' => $original->category_id,
                'brand_id' => $original->brand_id,
                'sku' => $sku,
                'name' => $original->name . ' (Copy)',
                'slug' => $slug,
                'description' => $original->description,
                'short_description' => $original->short_description,
                'price' => $original->price,
                'compare_at_price' => $original->compare_at_price,
                'quantity' => $original->quantity,
                'condition' => $original->condition,
                'status' => 'draft', // Always draft status
                'track_inventory' => $original->track_inventory,
                'low_stock_threshold' => $original->low_stock_threshold,
                'is_featured' => false, // Reset promotional flags
                'is_hot' => false,
                'video_url' => $original->video_url,
                'meta_title' => $original->meta_title,
                'meta_description' => $original->meta_description,
                'meta_keywords' => $original->meta_keywords,
            ]);

            // Duplicate thumbnail
            if ($original->thumbnail_path && Storage::disk('public')->exists($original->thumbnail_path)) {
                $ext = pathinfo($original->thumbnail_path, PATHINFO_EXTENSION);
                $newThumbPath = 'products/' . $duplicate->id . '/thumbnail.' . $ext;
                Storage::disk('public')->copy($original->thumbnail_path, $newThumbPath);
                $duplicate->update(['thumbnail_path' => $newThumbPath]);
            }

            // Duplicate media
            foreach ($original->media as $media) {
                if (Storage::disk('public')->exists($media->path)) {
                    $ext = pathinfo($media->path, PATHINFO_EXTENSION);
                    $newPath = 'products/' . $duplicate->id . '/media-' . $media->id . '.' . $ext;
                    Storage::disk('public')->copy($media->path, $newPath);
                    
                    ProductMedia::create([
                        'product_id' => $duplicate->id,
                        'type' => $media->type,
                        'path' => $newPath,
                        'alt_text' => $media->alt_text,
                        'sort_order' => $media->sort_order,
                        'is_thumbnail' => $media->is_thumbnail,
                    ]);
                }
            }

            // Duplicate documents
            foreach ($original->documents as $doc) {
                if (Storage::disk('public')->exists($doc->path)) {
                    $ext = pathinfo($doc->path, PATHINFO_EXTENSION);
                    $newDocPath = 'products/' . $duplicate->id . '/docs/doc-' . $doc->id . '.' . $ext;
                    Storage::disk('public')->copy($doc->path, $newDocPath);
                    
                    ProductDocument::create([
                        'product_id' => $duplicate->id,
                        'type' => $doc->type,
                        'label' => $doc->label,
                        'path' => $newDocPath,
                        'original_name' => $doc->original_name,
                        'sort_order' => $doc->sort_order,
                    ]);
                }
            }

            // Duplicate variants
            foreach ($original->variants as $variant) {
                $variantSku = 'DUP-VAR-' . strtoupper(Str::random(6));
                while (ProductVariant::where('sku', $variantSku)->exists()) {
                    $variantSku = 'DUP-VAR-' . strtoupper(Str::random(6));
                }

                $newVariant = ProductVariant::create([
                    'product_id' => $duplicate->id,
                    'sku' => $variantSku,
                    'name' => $variant->name,
                    'attributes' => $variant->attributes,
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'quantity' => $variant->quantity,
                ]);

                // Duplicate variant image if exists
                if ($variant->image_path && Storage::disk('public')->exists($variant->image_path)) {
                    $ext = pathinfo($variant->image_path, PATHINFO_EXTENSION);
                    $newVarImgPath = 'products/' . $duplicate->id . '/variants/var-' . $newVariant->id . '.' . $ext;
                    Storage::disk('public')->copy($variant->image_path, $newVarImgPath);
                    $newVariant->update(['image_path' => $newVarImgPath]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product duplicated successfully as draft. Edit and publish when ready.',
                'product' => $duplicate->fresh(['category', 'brand', 'media', 'documents', 'variants']),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $product = Product::where('store_id', $store->id)->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Check if approval is required
        $approvalRequired = \App\Models\Setting::get('product_approval_required', '0') === '1';
        $newStatus = $approvalRequired ? 'pending' : 'published';

        $product->update(['status' => $newStatus]);

        if ($approvalRequired) {
            try {
                Mail::to($user->email)->send(new ListingPendingApprovalMail(
                    $user->name ?: 'Seller',
                    $product->name,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $message = $approvalRequired 
            ? 'Product submitted for admin approval.' 
            : 'Product published successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'product' => $product,
            'requires_approval' => $approvalRequired,
        ]);
    }

    public function addFlashDeals(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
            'discount_type' => 'required|string|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'ends_at' => 'nullable|date',
        ]);

        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $productIds = array_unique($request->product_ids);
        $products = Product::where('store_id', $store->id)->whereIn('id', $productIds)->get();
        if ($products->count() !== count($productIds)) {
            return response()->json(['success' => false, 'message' => 'Some products not found or not yours'], 422);
        }

        $discountValue = $request->discount_type === 'percentage'
            ? min(100, (float) $request->discount_value)
            : (float) $request->discount_value;
        $endsAt = $request->filled('ends_at') ? $request->ends_at : null;

        foreach ($products as $product) {
            $product->update([
                'flash_deal_discount_type' => $request->discount_type,
                'flash_deal_discount_value' => $discountValue,
                'flash_deal_ends_at' => $endsAt,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($products) . ' product(s) added to Flash Deal',
            'count' => $products->count(),
        ]);
    }

    public function addNewArrivals(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No store'], 404);
        }

        $productIds = array_unique($request->product_ids);
        $updated = Product::where('store_id', $store->id)->whereIn('id', $productIds)->update(['is_new_arrival' => true]);
        if ($updated !== count($productIds)) {
            return response()->json(['success' => false, 'message' => 'Some products not found or not yours'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $updated . ' product(s) marked as New Arrival',
            'count' => $updated,
        ]);
    }

    private function checkPromotionEligibility(int $userId, int $storeId, string $type): bool
    {
        return Promotion::where('user_id', $userId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->whereHas('package', fn ($q) => $q->where('type', $type))
            ->exists();
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function getMappedValue(array $row, array $mapping, string $field): ?string
    {
        $col = $mapping[$field] ?? null;
        if (!$col || !isset($row[$col])) return null;
        $v = $row[$col];
        return $v !== null && $v !== '' ? (string) $v : null;
    }

    /**
     * Download image from URL and store in public disk. Returns storage path (e.g. products/1/abc.jpg) or null.
     */
    private function downloadImageToStorage(string $url, string $directory): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
            if (!$response->successful()) {
                return null;
            }
            $body = $response->body();
            $contentType = $response->header('Content-Type') ?? '';
            $ext = 'jpg';
            if (preg_match('/image\/(png|gif|webp|jpeg)/i', $contentType, $m)) {
                $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            } elseif (preg_match('/\.(png|gif|webp|jpeg|jpg)(\?|$)/i', $url, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            }
            $filename = Str::random(12) . '.' . $ext;
            $path = $directory . '/' . $filename;
            Storage::disk('public')->put($path, $body);
            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
