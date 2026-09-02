<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRule extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'name',
        'type',
        'rate',
        'min_order_amount',
        'free_threshold',
        'min_weight_kg',
        'max_weight_kg',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'free_threshold' => 'decimal:2',
        'min_weight_kg' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
