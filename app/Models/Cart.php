<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id', 'market'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->with('product');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(fn ($i) => (float) $i->price * $i->quantity);
    }

    public static function getOrCreate(?int $userId, ?string $sessionId = null): self
    {
        if ($userId) {
            $cart = static::firstOrCreate(
                ['user_id' => $userId],
                ['market' => 'PK']
            );
        } else {
            if (!$sessionId) {
                $sessionId = session()->getId();
            }
            $cart = static::firstOrCreate(
                ['session_id' => $sessionId, 'user_id' => null],
                ['market' => 'PK']
            );
        }
        return $cart;
    }
}
