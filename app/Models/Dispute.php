<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dispute extends Model
{
    protected $fillable = [
        'dispute_number', 'order_id', 'user_id', 'type', 'reason', 'description',
        'status', 'resolved_by', 'resolved_at', 'resolution_notes',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($d) {
            if (empty($d->dispute_number)) {
                $d->dispute_number = 'DSP-' . strtoupper(Str::random(8));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class, 'dispute_id');
    }
}
