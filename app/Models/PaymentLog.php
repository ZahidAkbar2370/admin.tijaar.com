<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    protected $table = 'payment_logs';

    protected $fillable = ['payment_id', 'event', 'payload', 'ip'];

    protected $casts = ['payload' => 'array'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
