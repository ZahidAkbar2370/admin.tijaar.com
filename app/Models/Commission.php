<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Commission extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'seller_type',
        'commission_type',
        'value',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderBy('id');
    }

    public static function calculateFor(float $amount, ?int $storeId = null, ?int $categoryId = null, string $sellerType = 'business'): float
    {
        $rule = static::resolveRule($storeId, $categoryId, $sellerType);
        if (!$rule) {
            return 0;
        }
        if ($rule->commission_type === 'percentage') {
            return round($amount * ($rule->value / 100), 2);
        }
        return min($rule->value, $amount);
    }

    public static function resolveRule(?int $storeId, ?int $categoryId, string $sellerType = 'business'): ?Commission
    {
        $rules = static::active()->ordered()->get();
        $best = null;
        $bestScore = -1;
        foreach ($rules as $r) {
            $score = 0;
            if ($r->scope_type === 'seller' && $r->scope_id && $storeId) {
                $store = \App\Models\Store::find($storeId);
                if ($store && $store->seller && (int) $store->seller->user_id === (int) $r->scope_id) {
                    $score = 1000;
                } else {
                    continue;
                }
            } elseif ($r->scope_type === 'category' && $r->scope_id && $categoryId && (int) $r->scope_id === (int) $categoryId) {
                $score = 100;
            } elseif ($r->scope_type === 'seller_type' && $r->seller_type === $sellerType) {
                $score = 50;
            } elseif ($r->scope_type === 'global') {
                $score = 1;
            } else {
                continue;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $r;
            }
        }
        return $best;
    }
}
