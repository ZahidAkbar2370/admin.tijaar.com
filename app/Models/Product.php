<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'seller_type',
        'seller_id',
        'store_id',
        'category_id',
        'brand_id',
        'sku',
        'name',
        'slug',
        'description',
        'short_description',
        'status',
        'oos_auto_inactive',
        'product_type',
        'condition',
        'price',
        'compare_at_price',
        'quantity',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'shipping_mode',
        'shipping_cost_cached',
        'track_inventory',
        'low_stock_threshold',
        'is_featured',
        'is_hot',
        'flash_deal_discount_type',
        'flash_deal_discount_value',
        'flash_deal_ends_at',
        'is_new_arrival',
        'thumbnail_path',
        'thumbnail_alt',
        'image_update_count',
        'video_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'shipping_cost_cached' => 'decimal:2',
        'flash_deal_discount_value' => 'decimal:2',
        'track_inventory' => 'boolean',
        'oos_auto_inactive' => 'boolean',
        'is_featured' => 'boolean',
        'is_hot' => 'boolean',
        'is_new_arrival' => 'boolean',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
        'shares_count' => 'integer',
        'flash_deal_ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = \App\Support\ProductSeoHelper::uniqueSlug((string) $product->name);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function flashDeals(): BelongsToMany
    {
        return $this->belongsToMany(FlashDeal::class, 'flash_deal_product')->withPivot('sort_order');
    }

    public function stockHistory(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    public function reservedStock(): HasMany
    {
        return $this->hasMany(ReservedStock::class)->where('expires_at', '>', now());
    }

    /**
     * Main image URL: thumbnail_path first, then first media (gallery).
     * Uses UploadHelper::url() so both public/upload and storage paths work.
     */
    public function getMainImageUrl(): ?string
    {
        $path = $this->thumbnail_path;
        if ($path) {
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            return \App\Support\UploadHelper::url($this->thumbnail_path);
        }
        $first = $this->media->first();
        if (!$first || empty($first->path)) {
            return null;
        }
        $path = ltrim($first->path, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return \App\Support\UploadHelper::url($first->path);
    }

    /**
     * For variable products with variants, effective quantity is the sum of variant quantities.
     * Otherwise the product's own quantity.
     */
    public function getEffectiveQuantity(): int
    {
        if (($this->product_type ?? 'simple') === 'variable') {
            $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
            if ($variants->isNotEmpty()) {
                return (int) $variants->sum('quantity');
            }
        }
        return (int) $this->quantity;
    }

    /**
     * Available quantity = quantity - reserved (non-expired).
     * Uses effective quantity for variable products (sum of variant quantities).
     *
     * Pass $excludeReferenceType + $excludeReferenceId to ignore reservations for the
     * current cart (so checkout / cart qty updates are not blocked by the buyer's own hold).
     */
    public function getAvailableQuantity(?string $excludeReferenceType = null, ?string $excludeReferenceId = null): int
    {
        $query = ReservedStock::where('product_id', $this->id)->where('expires_at', '>', now());
        if ($excludeReferenceType !== null && $excludeReferenceId !== null) {
            $query->whereRaw('NOT (reference_type = ? AND reference_id = ?)', [
                $excludeReferenceType,
                (string) $excludeReferenceId,
            ]);
        }
        $reserved = (int) $query->sum('quantity');
        return max(0, $this->getEffectiveQuantity() - $reserved);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Issue 15: Only products from non-suspended, non-banned sellers (store or private).
     */
    public function scopeFromActiveSellers($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('store_id')
                ->whereHas('store.seller.user', fn ($u) => $u->where('is_banned', false)->where('is_suspended', false))
                ->orWhereNull('store_id')
                ->whereHas('sellerUser', fn ($u) => $u->where('is_banned', false)->where('is_suspended', false));
        });
    }

    /**
     * Public catalog: hide out-of-stock products (unless inventory tracking is off).
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
                ->orWhere(function ($q2) {
                    // Simple (or null type): product quantity > 0
                    $q2->where(function ($q3) {
                        $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                    })->where('quantity', '>', 0);
                })
                ->orWhere(function ($q2) {
                    // Variable with variants: sum of variant qty > 0
                    $q2->where('product_type', 'variable')
                        ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) > 0');
                })
                ->orWhere(function ($q2) {
                    // Variable with no variants yet: fall back to product quantity
                    $q2->where('product_type', 'variable')
                        ->whereRaw('(SELECT COUNT(*) FROM product_variants WHERE product_id = products.id) = 0')
                        ->where('quantity', '>', 0);
                });
        });
    }

    public function isOutOfStock(): bool
    {
        if ($this->track_inventory === false) {
            return false;
        }
        return $this->getAvailableQuantity() <= 0;
    }
}
