<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\HomeFeaturedCategory;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\Testimonial;
use App\Services\PromotionDisplayHelper;
use App\Services\PromotionExpirationService;
use Illuminate\Http\JsonResponse;
use App\Support\HomeCache;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private const HOME_CACHE_TTL = 300; // 5 minutes

    /**
     * Single endpoint for home/landing page data to reduce round trips and speed up load.
     * Response is cached to keep home API fast.
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember(HomeCache::KEY, self::HOME_CACHE_TTL, fn () => $this->buildHomeData());

        return response()->json(array_merge(['success' => true], $data));
    }

    private function buildHomeData(): array
    {
        PromotionExpirationService::expireDue();

        $banners = Banner::where('position', 'home_hero')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'image' => $b->image_path ? \App\Support\UploadHelper::deliveryUrl($b->image_path, 1680, 78) : null,
                'image_alt' => $b->image_alt,
                'link_url' => $b->link_url,
                'description' => $b->description,
            ]);

        $sections = HomeSection::where('is_active', true)->orderBy('sort_order')->get(['key', 'title', 'config']);
        $sectionsConfig = $sections->pluck('config', 'key')->toArray();

        // Home UI: admin selects categories only — those categories and all their products appear on home
        $homeCategoryIds = HomeFeaturedCategory::orderBy('sort_order')->pluck('category_id')->toArray();

        if (!empty($homeCategoryIds)) {
            $featuredCategories = Category::with('parent')->whereIn('id', $homeCategoryIds)->get()->sortBy(fn ($c) => array_search($c->id, $homeCategoryIds))->values();
        } else {
            $featuredCategories = Category::active()->featured()->orderBy('sort_order')->limit(12)->get();
        }
        $categoryProductCounts = $this->buildCategoryProductCounts($featuredCategories);
        $categories = $featuredCategories
            ->map(fn ($c) => $this->formatCategoryForHome($c, $categoryProductCounts[$c->id] ?? 0))
            ->values();
        $featuredCategoryIds = $featuredCategories->pluck('id')->toArray();
        $categoryIdsForProducts = $featuredCategoryIds;

        // Hero / home featured: active paid featured_product promotions only (random order)
        $featuredProducts = $this->loadPromotedHomeProducts('featured_product', 24);

        // Hot sale: active paid hot_sale promotions only (random order)
        $hotSaleProducts = $this->loadPromotedHomeProducts('hot_sale', 24);

        // Featured verified sellers: active non-expired featured_shop promotion packages
        $featuredShopStoreIds = Promotion::query()
            ->active()
            ->whereHas('package', fn ($q) => $q->where('type', 'featured_shop'))
            ->whereNotNull('store_id')
            ->pluck('store_id')
            ->unique()
            ->values()
            ->all();
        $featuredShops = \App\Models\Store::query()
            ->with(['seller.user'])
            ->whereIn('id', $featuredShopStoreIds ?: [0])
            ->where('is_active', true)
            ->whereHas('seller', fn ($q) => $q->where('status', 'approved')->where('kyc_status', 'verified'))
            ->inRandomOrder()
            ->limit(12)
            ->get()
            ->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'logo' => $store->logo ? \App\Support\UploadHelper::deliveryUrl($store->logo, 128, 85) : null,
                'city' => $store->city,
                'description' => $store->description,
                'kyc_verified' => true,
            ]);

        // Best sellers: only products from KYC-verified sellers (approved + kyc_status=verified)
        $bestSellersQuery = $this->applyCatalogMetrics(Product::with(['media', 'category', 'store.seller', 'sellerUser.seller', 'variants']))
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->where(function ($q) {
                $kycApproved = fn ($s) => $s->where('status', 'approved')->where('kyc_status', 'verified');
                $q->whereHas('store.seller', $kycApproved)
                    ->orWhereHas('sellerUser.seller', $kycApproved);
            })
            ->orderByDesc('created_at')
            ->limit(24);
        $bestSellerProducts = $bestSellersQuery->get()->map(fn ($p) => $this->formatProduct($p));

        // All products section: more products from selected categories for carousel (limit 48)
        $allProductsQuery = $this->applyCatalogMetrics(Product::with(['media', 'category', 'store.seller', 'sellerUser.seller', 'variants']))
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->orderByDesc('created_at')
            ->limit(48);
        if (!empty($categoryIdsForProducts)) {
            $allProductsQuery->whereIn('category_id', $categoryIdsForProducts);
        } else {
            $allProductsQuery->whereRaw('1 = 0');
        }
        $allProducts = $allProductsQuery->get()->map(fn ($p) => $this->formatProduct($p));

        // Recent products: latest published products site-wide for auto-scroll section (limit 16)
        $recentProducts = $this->applyCatalogMetrics(Product::with(['media', 'category', 'store.seller', 'sellerUser.seller', 'variants']))
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->orderByDesc('created_at')
            ->limit(16)
            ->get()
            ->map(fn ($p) => $this->formatProduct($p));

        // Per-category product sections for home (e.g. "Electronics Products", "Fashion Products")
        $featuredProductsByCategory = [];
        foreach ($featuredCategories as $cat) {
            $catIds = array_values(array_unique(array_merge(
                [$cat->id],
                Category::where('parent_id', $cat->id)->pluck('id')->toArray()
            )));
            $catProducts = $this->applyCatalogMetrics(Product::with(['media', 'category', 'store.seller', 'sellerUser.seller', 'variants']))
                ->published()
                ->fromActiveSellers()
                ->inStock()
                ->whereIn('category_id', $catIds)
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();
            $featuredProductsByCategory[] = [
                'category' => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ],
                'products' => $catProducts->map(fn ($p) => $this->formatProduct($p))->values()->all(),
            ];
        }

        // Browse Categories section: all active root categories (no limit)
        $browseCategories = Category::active()->root()->orderBy('sort_order')->get();

        $featuredBrands = Brand::active()->featured()->orderBy('sort_order')->limit(12)->get();

        $flashDeals = FlashDeal::query()
            ->active()
            ->notExpired()
            ->with(['store:id,name,slug', 'products' => fn ($q) => $q->where('products.status', 'published')->inStock()->with(['media', 'variants'])])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($deal) => $this->formatFlashDeal($deal));

        $testimonials = Testimonial::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'role' => $t->role,
                'company' => $t->company,
                'avatar' => $t->avatar ? \App\Support\UploadHelper::url($t->avatar) : null,
                'avatar_alt' => $t->avatar_alt,
                'content' => $t->content,
                'rating' => $t->rating,
            ]);

        return [
            'banners' => $banners,
            'sections' => $sectionsConfig,
            'categories' => $categories,
            'featured_categories' => $categories,
            'browse_categories' => $browseCategories,
            'featured_products' => $featuredProducts,
            'hot_sale_products' => $hotSaleProducts,
            'featured_shops' => $featuredShops,
            'best_seller_products' => $bestSellerProducts,
            'all_products' => $allProducts,
            'recent_products' => $recentProducts,
            'featured_products_by_category' => $featuredProductsByCategory,
            'featured_brands' => $featuredBrands,
            'flash_deals' => $flashDeals,
            'testimonials' => $testimonials,
        ];
    }

    /**
     * Home promoted products: purchased, non-expired promotion packages only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadPromotedHomeProducts(string $packageType, int $limit): array
    {
        $productIds = Promotion::query()
            ->active()
            ->whereHas('package', fn ($q) => $q->where('type', $packageType))
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            return [];
        }

        $products = $this->applyCatalogMetrics(Product::with(['media', 'category', 'store.seller', 'sellerUser.seller', 'variants']))
            ->published()
            ->fromActiveSellers()
            ->inStock()
            ->whereIn('id', $productIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $promotionTypes = PromotionDisplayHelper::activeProductPromotionTypes(
            $products->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        return $products
            ->map(function ($p) use ($promotionTypes, $packageType) {
                $type = $promotionTypes[(int) $p->id] ?? $packageType;

                return PromotionDisplayHelper::attachPromotionFields(
                    $this->formatProduct($p),
                    $type
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, int> category_id => published product count (includes descendants)
     */
    private function buildCategoryProductCounts($featuredCategories): array
    {
        if ($featuredCategories->isEmpty()) {
            return [];
        }

        $allCategories = Category::active()->get(['id', 'parent_id']);
        $childrenByParent = $allCategories->groupBy('parent_id');

        $getDescendantIds = function (int $categoryId) use (&$getDescendantIds, $childrenByParent): array {
            $ids = [$categoryId];
            foreach ($childrenByParent->get($categoryId, collect()) as $child) {
                $ids = array_merge($ids, $getDescendantIds($child->id));
            }

            return array_values(array_unique($ids));
        };

        $descendantIdsByCategory = [];
        $allCategoryIds = [];
        foreach ($featuredCategories as $category) {
            $descendantIds = $getDescendantIds($category->id);
            $descendantIdsByCategory[$category->id] = $descendantIds;
            $allCategoryIds = array_merge($allCategoryIds, $descendantIds);
        }
        $allCategoryIds = array_values(array_unique($allCategoryIds));

        if (empty($allCategoryIds)) {
            return [];
        }

        $countsByCategoryId = Product::published()
            ->whereIn('category_id', $allCategoryIds)
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $result = [];
        foreach ($featuredCategories as $category) {
            $total = 0;
            foreach ($descendantIdsByCategory[$category->id] ?? [] as $categoryId) {
                $total += (int) ($countsByCategoryId[$categoryId] ?? 0);
            }
            $result[$category->id] = $total;
        }

        return $result;
    }

    private function formatCategoryForHome(Category $category, int $productsCount): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'image_url' => \App\Support\UploadHelper::deliveryUrl($category->image, 80)
                ?? \App\Support\UploadHelper::url($category->image),
            'image_alt' => $category->image_alt,
            'banner_image_alt' => $category->banner_image_alt,
            'icon' => $category->icon,
            'parent_id' => $category->parent_id,
            'products_count' => $productsCount,
        ];
    }

    private function formatProduct(Product $p): array
    {
        $store = $p->store;
        $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';
        $verified = $this->isKycVerifiedSeller($p);
        $variants = $p->relationLoaded('variants') ? $p->variants : $p->variants()->get();
        $firstVariant = $variants->first();
        $price = $firstVariant ? (float) $firstVariant->price : (float) $p->price;
        $originalPrice = $firstVariant && $firstVariant->compare_at_price
            ? (float) $firstVariant->compare_at_price
            : ($p->compare_at_price ? (float) $p->compare_at_price : null);
        $effectiveQty = (int) $p->getEffectiveQuantity();
        $availableQty = $p->track_inventory !== false ? $p->getAvailableQuantity() : $effectiveQty;
        $stockStatus = $p->track_inventory === false
            ? 'in_stock'
            : ($availableQty <= 0
                ? 'out_of_stock'
                : ($p->low_stock_threshold !== null && $availableQty <= (int) $p->low_stock_threshold ? 'low_stock' : 'in_stock'));

        $storeLogo = $store?->logo
            ? (\App\Support\UploadHelper::deliveryUrl($store->logo, 64, 85) ?? \App\Support\UploadHelper::url($store->logo))
            : null;

        $storeRating = $this->resolveStoreRating($store);
        $reviewCount = isset($p->reviews_count) ? (int) $p->reviews_count : 0;
        $ratingAvg = isset($p->rating_avg) && $p->rating_avg !== null
            ? round((float) $p->rating_avg, 1)
            : null;
        $totalSold = (int) ($p->total_sold ?? 0);

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'title' => $p->name,
            'price' => $price,
            'originalPrice' => $originalPrice,
            'image' => $this->productDeliveryImage($p),
            'image_alt' => $p->thumbnail_alt,
            'vendor' => $vendor,
            'vendor_slug' => $store?->slug,
            'vendor_logo' => $storeLogo,
            'vendor_logo_alt' => $store?->logo_alt,
            'vendor_city' => \App\Services\SellerOriginResolver::forProduct($p) ?: null,
            'store_rating' => $storeRating,
            'category' => $p->category?->name,
            'categorySlug' => $p->category?->slug,
            'quantity' => $effectiveQty,
            'available_quantity' => $availableQty,
            'stock_status' => $stockStatus,
            'track_inventory' => $p->track_inventory ?? true,
            'variant_options' => $this->buildVariantOptions($p),
            'verified' => $verified,
            'seller_type' => $p->seller_type ?? 'business',
            'is_featured' => (bool) ($p->is_featured ?? false),
            'is_hot' => (bool) ($p->is_hot ?? false),
            'show_promotion_diamond' => (bool) ($p->is_featured ?? false),
            'show_hot_deal' => (bool) ($p->is_hot ?? false),
            'promotion_type' => ($p->is_featured ?? false) ? 'featured_product' : null,
            'shipping_mode' => $p->shipping_mode ?? 'customer_pays',
            'shipping_cost_cached' => $p->shipping_cost_cached !== null ? (float) $p->shipping_cost_cached : null,
            'rating' => $ratingAvg,
            'reviews' => $reviewCount,
            'total_sold' => $totalSold,
            'sold' => $totalSold,
        ];
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

    /**
     * Blue verified tick: KYC approved private seller, or approved business seller with verified KYC.
     */
    private function isKycVerifiedSeller(Product $p): bool
    {
        $sellerType = $p->seller_type ?? 'business';
        $sellerUser = $p->relationLoaded('sellerUser')
            ? $p->sellerUser
            : ($p->seller_id ? $p->sellerUser()->first() : null);

        if ($sellerType === 'private') {
            return $sellerUser
                && ($sellerUser->is_private_seller ?? false)
                && ($sellerUser->private_seller_kyc_status ?? '') === 'approved';
        }

        // Business / store seller
        $store = $p->relationLoaded('store') ? $p->store : $p->store;
        $seller = null;
        if ($store) {
            $seller = $store->relationLoaded('seller') ? $store->seller : $store->seller()->first();
        }
        if (!$seller && $sellerUser) {
            $seller = $sellerUser->relationLoaded('seller') ? $sellerUser->seller : $sellerUser->seller()->first();
        }

        return $seller
            && $seller->status === 'approved'
            && ($seller->kyc_status ?? '') === 'verified';
    }

    /** @var array<int, float|null> */
    private array $storeRatingCache = [];

    private function resolveStoreRating(?\App\Models\Store $store): ?float
    {
        if (!$store) {
            return null;
        }
        if (array_key_exists($store->id, $this->storeRatingCache)) {
            return $this->storeRatingCache[$store->id];
        }
        $reviews = \DB::table('reviews')
            ->where('reviewable_type', 'App\Models\Store')
            ->where('reviewable_id', $store->id)
            ->where('status', 'approved');
        $count = (clone $reviews)->count();
        $avg = $count > 0 ? round((float) (clone $reviews)->avg('rating'), 1) : null;
        $this->storeRatingCache[$store->id] = $avg;

        return $avg;
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

    private function formatFlashDeal(FlashDeal $deal): array
    {
        $products = $deal->relationLoaded('products') ? $deal->products : $deal->products()->where('products.status', 'published')->with('variants')->get();
        $productList = $products->map(function ($p) {
            $pivot = $p->pivot;
            $variantId = $pivot && $pivot->variant_id ? (int) $pivot->variant_id : null;
            $variant = $variantId && $p->relationLoaded('variants') ? $p->variants->firstWhere('id', $variantId) : null;
            $price = $variant ? (float) $variant->price : (float) $p->price;
            $compareAt = $variant && $variant->compare_at_price ? (float) $variant->compare_at_price : ($p->compare_at_price ? (float) $p->compare_at_price : null);
            $image = $variant && $variant->image_path
                ? (\App\Support\UploadHelper::deliveryUrl($variant->image_path, 280, 78) ?? \App\Support\UploadHelper::url($variant->image_path))
                : $this->productDeliveryImage($p);
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $price,
                'compare_at_price' => $compareAt,
                'image' => $image,
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
            'image_alt' => $deal->image_alt,
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

    private function productDeliveryImage(Product $p, int $width = 280): ?string
    {
        $path = $p->thumbnail_path;
        if (! $path) {
            $media = $p->relationLoaded('media') ? $p->media->first() : $p->media()->first();
            $path = $media?->path;
        }

        if ($path) {
            return \App\Support\UploadHelper::deliveryUrl($path, $width, 78) ?? $p->getMainImageUrl();
        }

        return $p->getMainImageUrl();
    }
}
