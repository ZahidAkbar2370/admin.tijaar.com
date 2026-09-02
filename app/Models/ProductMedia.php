<?php

namespace App\Models;

use App\Support\UploadHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $fillable = ['product_id', 'type', 'path', 'alt_text', 'sort_order', 'is_thumbnail'];

    protected $casts = [
        'is_thumbnail' => 'boolean',
    ];

    /** Full URL for API consumers (e.g. Flutter app) so images display without building URL from path. */
    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return UploadHelper::url($this->path);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
