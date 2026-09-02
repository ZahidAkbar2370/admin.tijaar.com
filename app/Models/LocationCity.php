<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationCity extends Model
{
    protected $fillable = [
        'province_id',
        'name',
        'leopards_city_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'province_id' => 'integer',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(LocationProvince::class, 'province_id');
    }
}
