<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $appends = ['logo_url'];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'logo',
        'logo_alt',
        'website',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }
        $path = ltrim($this->logo, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return \App\Support\UploadHelper::deliveryUrl($this->logo, 100)
            ?? \App\Support\UploadHelper::url($this->logo);
    }
}
