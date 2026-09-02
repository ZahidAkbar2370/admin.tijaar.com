<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationProvince extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'country_id' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(LocationCountry::class, 'country_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(LocationCity::class, 'province_id')->orderBy('sort_order')->orderBy('name');
    }
}
