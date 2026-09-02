<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservedStock extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'reference_type',
        'reference_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function reserve(int $productId, int $quantity, string $referenceType, string $referenceId, int $ttlMinutes = 60): ?self
    {
        $product = Product::find($productId);
        if (!$product || $product->quantity < $quantity) {
            return null;
        }
        $existing = static::where('product_id', $productId)->sum('quantity');
        $available = max(0, (int) $product->quantity - $existing);
        if ($available < $quantity) {
            return null;
        }
        return static::create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public static function releaseFor(string $referenceType, string $referenceId): int
    {
        $rows = static::where('reference_type', $referenceType)->where('reference_id', $referenceId)->get();
        $count = $rows->count();
        foreach ($rows as $row) {
            $row->delete();
        }
        return $count;
    }
}
