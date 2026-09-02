<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payout extends Model
{
    protected $fillable = [
        'payout_number',
        'user_id',
        'seller_type',
        'amount',
        'status',
        'method',
        'bank_account_holder',
        'bank_account_number',
        'bank_name',
        'notes',
        'approved_by',
        'approved_at',
        'paid_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public static function generatePayoutNumber(): string
    {
        $prefix = 'PO';
        $date = date('ymd');
        $seq = static::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
