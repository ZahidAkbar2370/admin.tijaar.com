<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapStaticUrl extends Model
{
    protected $fillable = [
        'path',
        'changefreq',
        'priority',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'priority' => 'float',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
