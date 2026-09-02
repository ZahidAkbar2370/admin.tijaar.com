<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'name', 'attributes', 'price', 'compare_at_price',
        'quantity', 'image_path', 'image_alt', 'image_paths',
    ];

    protected $casts = [
        'attributes' => 'array',
        'image_paths' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
