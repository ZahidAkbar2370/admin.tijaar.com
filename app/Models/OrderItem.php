<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'store_id',
        'seller_id',
        'product_name',
        'product_sku',
        'product_image_path',
        'quantity',
        'price',
        'commission_amount',
        'marketplace_fee_allocated',
        'online_transaction_fee_allocated',
        'discount_allocated',
        'seller_type',
        'fulfillment_status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'refund_amount',
        'options',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'marketplace_fee_allocated' => 'decimal:2',
        'online_transaction_fee_allocated' => 'decimal:2',
        'discount_allocated' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'options' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Include soft-deleted listings so order history keeps product info/images.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Listing still publicly available (not soft-deleted). */
    public function isProductAvailable(): bool
    {
        return $this->product !== null && ! $this->product->trashed();
    }

    /**
     * Prefer variant image, then snapshotted path, then live product (incl. trashed).
     */
    public function resolveImageUrl(?string $variantImagePath = null): ?string
    {
        if ($variantImagePath) {
            return \App\Support\UploadHelper::url($variantImagePath);
        }
        if ($this->product_image_path) {
            return \App\Support\UploadHelper::url($this->product_image_path);
        }
        if ($this->product) {
            return $this->product->getMainImageUrl();
        }

        return null;
    }

    /** Snapshot path for checkout (variant → thumbnail → first media). */
    public static function snapshotImagePath($product, $variant = null): ?string
    {
        if ($variant) {
            $fromVariant = $variant->image_path
                ?? (is_array($variant->image_paths ?? null) && ! empty($variant->image_paths)
                    ? $variant->image_paths[0]
                    : null);
            if ($fromVariant) {
                return $fromVariant;
            }
        }
        if ($product?->thumbnail_path) {
            return $product->thumbnail_path;
        }
        $media = $product?->relationLoaded('media')
            ? $product->media
            : $product?->media()->orderByDesc('is_thumbnail')->orderBy('sort_order')->orderBy('id')->get();
        $first = $media?->first();

        return $first?->path ?: null;
    }
}
