<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'store_id',
        'seller_id',
        'shipping_cost',
        'carrier',
        'tracking_number',
        'tracking_url',
        'lcs_booking_id',
        'lcs_cn_number',
        'lcs_raw_response',
        'tcs_booking_id',
        'tcs_cn_number',
        'tcs_raw_response',
        'weight_kg',
        'pieces',
        'pickup_type',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'pieces' => 'integer',
        'lcs_raw_response' => 'array',
        'tcs_raw_response' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
