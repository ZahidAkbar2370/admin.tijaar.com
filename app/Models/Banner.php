<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'position', 'image_path', 'image_alt', 'link_url', 'description', 'is_active', 'starts_at', 'ends_at', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
}
