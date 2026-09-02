<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'market',
        'country',
        'regions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'regions' => 'array',
        'is_active' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(ShippingRule::class, 'shipping_zone_id')->where('is_active', true)->orderBy('sort_order');
    }

    public function allRules(): HasMany
    {
        return $this->hasMany(ShippingRule::class, 'shipping_zone_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMarket($query, string $market)
    {
        return $query->where('market', $market);
    }

    public function scopeForCountry($query, ?string $country)
    {
        if (!$country) {
            return $query;
        }
        $country = trim($country);
        return $query->where(function ($q) use ($country) {
            $q->whereNull('country')
                ->orWhere('country', $country)
                ->orWhere('country', 'LIKE', $country . '%');
        });
    }
}
