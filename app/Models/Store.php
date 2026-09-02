<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Store extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'slug',
        'description',
        'logo',
        'logo_alt',
        'banner',
        'banner_alt',
        'cover_image',
        'cover_image_alt',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'phone',
        'email',
        'meta_title',
        'meta_description',
        'shipping_policy',
        'return_policy',
        'payment_methods',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name);
            }
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_followers')->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function flashDeals(): HasMany
    {
        return $this->hasMany(FlashDeal::class)->orderBy('sort_order');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
