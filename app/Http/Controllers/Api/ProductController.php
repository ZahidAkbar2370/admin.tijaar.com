<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Services\PromotionDisplayHelper;
use App\Support\ProductSeoHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['store.seller', 'sellerUser.seller', 'category', 'media', 'variants'])
            ->published()
            ->fromActiveSellers()
            ->inStock();

        $this->applyCatalogMetrics($query);

        if ($request->filled('category_id')) {
            $categoryIds = $this->categoryIdsIncludingDescendants([(int) $request->category_id]);
            $query->whereIn('category_id', $categoryIds ?: [0]);
        }
        if ($request->filled('category_slug')) {
            $category = \App\Models\Category::query()->where('slug', $request->category_slug)->first();
            if ($category) {
                $categoryIds = $this->categoryIdsIncludingDescendants([(int) $category->id]);
                $query->whereIn('category_id', $categoryIds ?: [0]);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('store_slug')) {
            $query->whereHas('store', fn ($q) => $q->where('slug', $request->store_slug));
        }
        if ($request->filled('search')) {
            $q = \Illuminate\Support\Str::limit($request->search, 255, '');
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
            });
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('seller_type') && in_array($request->seller_type, ['business', 'private'], true)) {
            $query->where('seller_type', $request->seller_type);
        }
        if ($request->filled('availability')) {
            $avail = $request->availability;
            if ($avail === 'in_stock') {
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                        })->where('quantity', '>', 0);
                    })->orWhereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) > 0');
                });
            } elseif ($avail === 'low_stock') {
                $query->whereNotNull('low_stock_threshold')
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                            })->whereColumn('quantity', '<=', 'low_stock_threshold')->where('quantity', '>', 0);
                        })->orWhereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) > 0 AND (SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) <= low_stock_threshold');
                    });
            }
        }
        if ($request->filled('rating')) {
            $star = (int) $request->rating;
            if ($star >= 1 && $star <= 5) {
                $productIds = \DB::table('reviews')
                    ->where('reviewable_type', 'App\Models\Product')
                    ->where('status', 'approved')
                    ->selectRaw('reviewable_id as product_id, AVG(rating) as avg_rating')
                    ->groupBy('reviewable_id');
                if ($star === 5) {
                    $productIds->havingRaw('AVG(rating) >= 4.5 AND AVG(rating) <= 5');
                } else {
                    $productIds->havingRaw('AVG(rating) >= ? AND AVG(rating) < ?', [(float) $star, (float) ($star + 1)]);
                }
                $ids = $productIds->pluck('product_id');
                $query->whereIn('id', $ids->isEmpty() ? [0] : $ids);
            }
        } elseif ($request->filled('min_rating')) {
            $minRating = (float) $request->min_rating;
            $productIds = \DB::table('reviews')
                ->where('reviewable_type', 'App\Models\Product')
                ->where('status', 'approved')
                ->selectRaw('reviewable_id as product_id, AVG(rating) as avg_rating')
                ->groupBy('reviewable_id')
                ->having('avg_rating', '>=', $minRating)
                ->pluck('product_id');
            $query->whereIn('id', $productIds->isEmpty() ? [0] : $productIds);
        }
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }
        if ($request->boolean('verified_sellers')) {
            $query->where(function ($q) {
                $kycApproved = fn ($s) => $s->where('status', 'approved')->where('kyc_status', 'verified');
                $q->whereHas('store.seller', $kycApproved)
                    ->orWhereHas('sellerUser.seller', $kycApproved);
            });
        }

        $sort = $request->get('sort', 'newest');
        $minPriceSubquery = '(SELECT COALESCE(MIN(CAST(pv.price AS DECIMAL(12,2))), 999999999) FROM product_variants pv WHERE pv.product_id = products.id)';
        $sortPriceExpr = "CASE WHEN product_type = 'variable' THEN {$minPriceSubquery} ELSE COALESCE(CAST(price AS DECIMAL(12,2)), 999999999) END";

        $this->applyCatalogRanking($query);

        match ($sort) {
            'price_asc' => $query->orderByRaw("{$sortPriceExpr} ASC")->orderBy('id', 'desc'),
            'price_desc' => $query->orderByRaw("CASE WHEN product_type = 'variable' THEN (SELECT COALESCE(MIN(CAST(pv.price AS DECIMAL(12,2))), 0) FROM product_variants pv WHERE pv.product_id = products.id) ELSE COALESCE(CAST(price AS DECIMAL(12,2)), 0) END DESC")->orderBy('id', 'desc'),
            'new_arrivals' => $query->orderByRaw('CASE WHEN is_new_arrival = 1 THEN 0 ELSE 1 END ASC')->orderBy('created_at', 'desc'),
            'rating' => $query->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
                ->orderByDesc('rating_avg')->orderBy('id', 'desc'),
            'popular' => $query->orderByDesc('created_at')->orderBy('id', 'desc'),
            'relevance' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate($request->get('per_page', 20))->withQueryString();

        $productIds = $products->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $promotionTypes = PromotionDisplayHelper::activeProductPromotionTypes($productIds);

        $data = $products->getCollection()->map(function ($p) use ($promotionTypes) {
            $item = $this->formatProduct($p);
            return PromotionDisplayHelper::attachPromotionFields(
                $item,
                $promotionTypes[(int) $p->id] ?? null
            );
        });
        $products->setCollection($data);

        return response()->json([
            'success' => true,
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['store.seller.user', 'sellerUser', 'category', 'brand', 'media', 'variants', 'documents'])
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'product' => PromotionDisplayHelper::attachPromotionFields(
                $this->formatProduct($product, true),
                PromotionDisplayHelper::activeProductPromotionTypes([(int) $product->id])[(int) $product->id] ?? null
            ),
        ]);
    }

    /**
     * Random promoted products for product-detail advertisements.
     */
    public function promotedAds(Request $request): JsonResponse
    {
        $excludeId = (int) $request->input('exclude_id', 0);
        $limit = min(8, max(1, (int) $request->input('limit', 4)));

        $promotedIds = Promotion::query()
            ->active()
            ->whereHas('package', fn ($q) => $q->whereIn('type', ['featured_product', 'hot_sale']))
            ->whereNotNull('product_id')
            ->when($excludeId > 0, fn ($q) => $q->where('product_id', '!=', $excludeId))
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        if ($promotedIds === []) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $products = Product::with(['store.seller', 'sellerUser.seller', 'category', 'media', 'variants'])
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->whereIn('id', $promotedIds)
            ->where(function ($q) {
                $q->where('is_featured', true)->orWhere('is_hot', true);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $promotionTypes = PromotionDisplayHelper::activeProductPromotionTypes(
            $products->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $items = $products->map(function ($p) use ($promotionTypes) {
            return PromotionDisplayHelper::attachPromotionFields(
                $this->formatProduct($p),
                $promotionTypes[(int) $p->id] ?? null
            );
        })->values();

        return response()->json(['success' => true, 'products' => $items]);
    }

    /**
     * Shop/catalog ranking: Featured/Hot promotions first, then business, then private.
     */
    private function applyCatalogRanking($query): void
    {
        $now = now()->toDateTimeString();
        $query->orderByRaw(
            "CASE
                WHEN EXISTS (
                    SELECT 1 FROM promotions
                    INNER JOIN promotion_packages ON promotion_packages.id = promotions.promotion_package_id
                    WHERE promotions.product_id = products.id
                      AND promotions.status = 'active'
                      AND promotions.starts_at <= ?
                      AND promotions.ends_at >= ?
                      AND (promotions.payment_status IS NULL OR promotions.payment_status = 'paid')
                      AND promotion_packages.type IN ('featured_product', 'hot_sale')
                ) THEN 0
                WHEN products.seller_type = 'business' THEN 1
                WHEN products.seller_type = 'private' THEN 2
                ELSE 3
            END ASC",
            [$now, $now]
        );
    }

    /**
     * Include a category and all nested children for shop filtering.
     *
     * @param  list<int>  $rootIds
     * @return list<int>
     */
    private function categoryIdsIncludingDescendants(array $rootIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $rootIds))));
        if ($ids === []) {
            return [];
        }

        $frontier = $ids;
        for ($depth = 0; $depth < 8 && $frontier !== []; $depth++) {
            $children = \App\Models\Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $new = array_diff($children, $ids);
            if ($new === []) {
                break;
            }
            $ids = array_values(array_unique(array_merge($ids, $new)));
            $frontier = array_values($new);
        }

        return $ids;
    }

    private function getStoreSellerDetail(Product $p): ?array
    {
        $store = $p->store;
        if (!$store) {
            return null;
        }

        $seller = $store->seller;
        $user = $seller?->user;
        $totalProducts = Product::where('store_id', $store->id)->published()->count();
        $productReviews = $this->productReviewStatsForStore($store->id);

        // Successful / completed orders for this store (delivered or completed).
        $completedOrders = (int) \DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.store_id', $store->id)
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->distinct()
            ->count('order_items.order_id');

        // Also count seller shipments marked delivered when the parent order is still multi-seller.
        $deliveredShipments = (int) \DB::table('shipments')
            ->where('store_id', $store->id)
            ->whereIn('status', ['delivered', 'completed'])
            ->distinct()
            ->count('order_id');

        $completedOrders = max($completedOrders, $deliveredShipments);

        return [
            'vendor' => $store->name,
            'vendor_slug' => $store->slug,
            'vendor_logo' => $store->logo ? \App\Support\UploadHelper::url($store->logo) : null,
            'vendor_logo_alt' => $store->logo_alt,
            'total_products' => $totalProducts,
            'completed_orders' => $completedOrders,
            'store_rating' => $productReviews['rating'],
            'store_reviews_count' => $productReviews['count'],
            'product_rating' => $productReviews['rating'],
            'product_reviews_count' => $productReviews['count'],
            'verified' => $seller && $seller->status === 'approved',
            'kyc_verified' => $seller && ($seller->kyc_status ?? '') === 'verified',
            'email_verified' => $user && $user->email_verified_at !== null,
            'phone_verified' => $user && $user->phone_verified_at !== null,
            'store_verified' => $seller && $seller->status === 'approved' && $store->is_active,
            'is_store' => true,
            'shipping_policy' => $store->shipping_policy,
            'return_policy' => $store->return_policy,
        ];
    }

    /** @return array{rating: ?float, count: int} */
    private function productReviewStatsForStore(int $storeId): array
    {
        $row = \DB::table('reviews')
            ->join('products', function ($join) {
                $join->on('reviews.reviewable_id', '=', 'products.id')
                    ->where('reviews.reviewable_type', '=', 'App\Models\Product');
            })
            ->where('products.store_id', $storeId)
            ->where('reviews.status', 'approved')
            ->selectRaw('ROUND(AVG(reviews.rating), 1) as avg_rating, COUNT(reviews.id) as review_count')
            ->first();

        return [
            'rating' => $row && $row->avg_rating !== null ? (float) $row->avg_rating : null,
            'count' => (int) ($row->review_count ?? 0),
        ];
    }

    private function formatProduct(Product $p, bool $detail = false): array
    {
        $store = $p->store;
        $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';

        $reviews = $p->reviews()->approved();
        $reviewCount = isset($p->reviews_count)
            ? (int) $p->reviews_count
            : $reviews->count();
        $ratingAvg = isset($p->rating_avg)
            ? ($p->rating_avg !== null ? round((float) $p->rating_avg, 1) : null)
            : ($reviewCount > 0 ? round($reviews->avg('rating'), 1) : null);
        $totalSold = (int) ($p->total_sold ?? 0);

        $sellerId = $store?->seller?->user_id ?? $p->seller_id;

        $storeLogo = $store?->logo ? \App\Support\UploadHelper::url($store->logo) : null;

        // Gallery: all media images (thumbnail + gallery). Main image prefers dedicated thumbnail.
        $thumbnailUrl = $p->thumbnail_path ? \App\Support\UploadHelper::url($p->thumbnail_path) : null;
        $mediaUrls = $p->media
            ->sortBy('sort_order')
            ->map(fn ($m) => $m->path ? \App\Support\UploadHelper::url($m->path) : null)
            ->filter()
            ->values()
            ->unique()
            ->toArray();

        if ($thumbnailUrl && ! in_array($thumbnailUrl, $mediaUrls, true)) {
            array_unshift($mediaUrls, $thumbnailUrl);
        }

        $imagesArray = $mediaUrls;
        $mainImageUrl = $thumbnailUrl ?: ($imagesArray[0] ?? null);

        $variants = $p->relationLoaded('variants') ? $p->variants : $p->variants()->get();
        // Variable product with no product-level images: use first variant image so thumbnail appears on detail
        if (empty($imagesArray) && $variants->isNotEmpty()) {
            foreach ($variants as $v) {
                $vp = $v->image_path ?? (is_array($v->image_paths ?? null) && !empty($v->image_paths) ? $v->image_paths[0] : null);
                if ($vp) {
                    $mainImageUrl = \App\Support\UploadHelper::url($vp);
                    $imagesArray = [$mainImageUrl];
                    break;
                }
            }
        }
        $firstVariant = $variants->first();
        $displayPrice = $firstVariant ? (float) $firstVariant->price : (float) $p->price;
        $displayOriginalPrice = $firstVariant && $firstVariant->compare_at_price
            ? (float) $firstVariant->compare_at_price
            : ($p->compare_at_price ? (float) $p->compare_at_price : null);

        $item = [
            'id' => $p->id,
            'slug' => $p->slug,
            'sku' => $p->sku,
            'name' => $p->name,
            'title' => $p->name,
            'price' => $displayPrice,
            'originalPrice' => $displayOriginalPrice,
            'image' => $mainImageUrl,
            'image_alt' => $p->thumbnail_alt,
            'images' => $imagesArray,
            'vendor' => $vendor,
            'vendor_slug' => $store?->slug,
            'vendor_logo' => $storeLogo,
            'vendor_logo_alt' => $store?->logo_alt,
            'vendor_city' => \App\Services\SellerOriginResolver::forProduct($p) ?: null,
            'store_id' => $p->store_id ? (int) $p->store_id : null,
            'seller_id' => $sellerId,
            'category' => $p->category?->name,
            'categorySlug' => $p->category?->slug,
            'seller_type' => $p->seller_type ?? 'business',
            'is_featured' => (bool) ($p->is_featured ?? false),
            'is_hot' => (bool) ($p->is_hot ?? false),
            'product_type' => $p->product_type ?? (($p->variants()->count() > 0) ? 'variable' : 'simple'),
            'rating' => $ratingAvg !== null ? (float) $ratingAvg : null,
            'reviews' => (int) $reviewCount,
            'total_sold' => $totalSold,
            'sold' => $totalSold,
            'short_description' => $p->short_description,
        ];

        $item['quantity'] = $p->getEffectiveQuantity();
        $item['available_quantity'] = $p->track_inventory !== false ? $p->getAvailableQuantity() : $p->getEffectiveQuantity();
        $item['track_inventory'] = $p->track_inventory ?? true;
        $item['low_stock_threshold'] = $p->low_stock_threshold;
        $avail = $item['available_quantity'];
        $item['stock_status'] = $p->track_inventory === false ? 'in_stock' : ($avail <= 0 ? 'out_of_stock' : ($p->low_stock_threshold !== null && $avail <= (int) $p->low_stock_threshold ? 'low_stock' : 'in_stock'));

        $item['variant_options'] = $this->buildVariantOptions($p);
        $seller = $store?->seller ?? $p->sellerUser?->seller ?? null;
        $sellerType = $p->seller_type ?? 'business';
        if ($sellerType === 'private') {
            $sellerUser = $p->sellerUser;
            $item['verified'] = $sellerUser
                && ($sellerUser->is_private_seller ?? false)
                && ($sellerUser->private_seller_kyc_status ?? '') === 'approved';
        } else {
            $item['verified'] = $seller
                && $seller->status === 'approved'
                && ($seller->kyc_status ?? '') === 'verified';
        }
        $item['shipping_mode'] = $p->shipping_mode ?? 'customer_pays';
        $item['shipping_cost_cached'] = $p->shipping_cost_cached !== null ? (float) $p->shipping_cost_cached : null;
        if ($store) {
            $storeReviews = \DB::table('reviews')
                ->where('reviewable_type', 'App\Models\Store')
                ->where('reviewable_id', $store->id)
                ->where('status', 'approved');
            $storeReviewCount = (clone $storeReviews)->count();
            $item['store_rating'] = $storeReviewCount > 0 ? round((float) (clone $storeReviews)->avg('rating'), 1) : null;
        } else {
            $item['store_rating'] = null;
        }

        if ($detail) {
            $item['description'] = $p->description;
            $item['short_description'] = $p->short_description;
            $item['condition'] = $p->condition;
            $item['brand'] = $p->brand?->name;
            $item['brand_slug'] = $p->brand?->slug;
            $item['tags'] = []; // optional: load from product_tag if Tag model exists
            $item['is_featured'] = (bool) ($p->is_featured ?? false);
            $item['is_hot'] = (bool) ($p->is_hot ?? false);
            $item['is_new_arrival'] = (bool) ($p->is_new_arrival ?? false);
            $item['flash_deal_discount_type'] = $p->flash_deal_discount_type;
            $item['flash_deal_discount_value'] = $p->flash_deal_discount_value ? (float) $p->flash_deal_discount_value : null;
            $item['flash_deal_ends_at'] = $p->flash_deal_ends_at?->toIso8601String();
            $storeSeller = $this->getStoreSellerDetail($p);
            if ($storeSeller) {
                $item['seller_card'] = $storeSeller;
                $item['shipping_policy'] = $storeSeller['shipping_policy'];
                $item['return_policy'] = $storeSeller['return_policy'];
            }
            $item['variants'] = $p->variants->map(function ($v) {
                $paths = $v->image_paths ?? [];
                if (!is_array($paths)) $paths = [];
                if ($v->image_path && !in_array($v->image_path, $paths, true)) {
                    array_unshift($paths, $v->image_path);
                }
                $imageUrls = array_values(array_map(fn ($path) => \App\Support\UploadHelper::url($path), array_filter($paths)));
                // So product detail and cart can show variant thumbnail: use image_path or first of image_paths
                $variantImageUrl = $v->image_path
                    ? \App\Support\UploadHelper::url($v->image_path)
                    : (!empty($imageUrls) ? $imageUrls[0] : null);
                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->name,
                    'attributes' => $v->attributes ?? [],
                    'price' => (float) $v->price,
                    'compare_at_price' => $v->compare_at_price ? (float) $v->compare_at_price : null,
                    'quantity' => (int) $v->quantity,
                    'image_url' => $variantImageUrl,
                    'image_urls' => $imageUrls,
                ];
            })->values()->toArray();
            $item['video_url'] = $p->video_url ? (string) $p->video_url : null;
            $meta = ProductSeoHelper::resolve([
                'name' => $p->name,
                'short_description' => $p->short_description,
                'description' => $p->description,
                'meta_title' => $p->meta_title,
                'meta_description' => $p->meta_description,
                'meta_keywords' => $p->meta_keywords,
            ]);
            $item['meta_title'] = $meta['meta_title'];
            $item['meta_description'] = $meta['meta_description'];
            $item['meta_keywords'] = $meta['meta_keywords'];
            $item['documents'] = $p->relationLoaded('documents') && $p->documents->isNotEmpty()
                ? $p->documents->map(fn ($d) => [
                    'id' => $d->id,
                    'url' => $d->path ? \App\Support\UploadHelper::url($d->path) : null,
                    'label' => $d->label ?: $d->original_name ?: 'Document',
                ])->values()->toArray()
                : [];
        }

        return $item;
    }

    private function applyCatalogMetrics($query)
    {
        return $query
            ->withSum(['orderItems as total_sold' => function ($q) {
                $q->whereNotIn('fulfillment_status', ['rejected', 'cancelled'])
                    ->whereHas('order', fn ($oq) => $oq->whereNotIn('status', ['cancelled', 'refunded']));
            }], 'quantity')
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($q) => $q->where('status', 'approved')]);
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
}
