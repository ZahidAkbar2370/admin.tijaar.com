<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $appends = ['image_url', 'icon_url', 'banner_image_url'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'image_alt',
        'banner_image',
        'banner_image_alt',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }
        $path = ltrim($this->image, '/');
        if (str_starts_with($path, 'http')) {
            return \App\Support\UploadHelper::deliveryUrl($path, 96) ?? $path;
        }

        return \App\Support\UploadHelper::deliveryUrl($this->image, 80)
            ?? \App\Support\UploadHelper::url($this->image);
    }

    public function getBannerImageUrlAttribute(): ?string
    {
        if (empty($this->banner_image)) {
            return null;
        }
        $path = ltrim($this->banner_image, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, 'upload/')) {
            return asset($path);
        }
        return asset('storage/' . $path);
    }

    public function getIconUrlAttribute(): ?string
    {
        if (empty($this->icon)) {
            return null;
        }
        $path = ltrim($this->icon, '/');
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        $path = str_starts_with($path, 'storage/') ? $path : 'storage/' . $path;
        return url($path);
    }

    public static function tree()
    {
        return static::with('children.children.children')
            ->root()
            ->orderBy('sort_order')
            ->get();
    }
}
