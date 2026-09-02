<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'featured_image', 'featured_image_alt', 'meta_title', 'meta_description', 'meta_keywords', 'author_id', 'is_published', 'published_at', 'views_count'];

    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
