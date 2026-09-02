<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    protected $fillable = [
        'return_number', 'order_id', 'order_item_id', 'user_id',
        'reason', 'description', 'status', 'tracking_number',
        'approved_at', 'approved_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($r) {
            if (empty($r->return_number)) {
                $r->return_number = 'RET-' . strtoupper(Str::random(8));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
