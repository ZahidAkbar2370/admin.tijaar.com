<?php

namespace App\Models;

use App\Support\WalletTransactionLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    protected $appends = [
        'title',
    ];

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \RuntimeException('Wallet transaction history cannot be deleted.');
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function getTitleAttribute(): string
    {
        return WalletTransactionLabel::title(
            (string) $this->type,
            $this->amount,
            is_array($this->meta) ? $this->meta : null
        );
    }
}
