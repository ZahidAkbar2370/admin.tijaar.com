<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PromotionPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'price',
        'duration_days',
        'seller_type_eligibility',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($pkg) {
            if (empty($pkg->slug)) {
                $pkg->slug = Str::slug($pkg->name);
            }
        });
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'promotion_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
