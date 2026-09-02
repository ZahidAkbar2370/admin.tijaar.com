<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class FlashDeal extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'image_path',
        'image_alt',
        'discount_type',
        'discount_value',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($deal) {
            if (empty($deal->slug)) {
                $deal->slug = Str::slug($deal->name);
                $original = $deal->slug;
                $count = 0;
                while (static::where('slug', $deal->slug)->exists()) {
                    $deal->slug = $original . '-' . (++$count);
                }
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_deal_product')
            ->withPivot('sort_order', 'variant_id')
            ->orderBy('flash_deal_product.sort_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }
        $path = ltrim($this->image_path, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return \App\Support\UploadHelper::deliveryUrl($this->image_path, 280)
            ?? \App\Support\UploadHelper::url($this->image_path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
        });
    }
}
