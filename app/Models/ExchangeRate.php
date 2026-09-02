<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = ['from_currency', 'to_currency', 'rate', 'effective_from', 'effective_until'];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'rate' => 'decimal:8',
    ];

    public static function getCurrentRate(string $from, string $to): ?float
    {
        $record = static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', now());
            })
            ->latest('effective_from')
            ->first();
        return $record ? (float) $record->rate : null;
    }
}
