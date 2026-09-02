<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTimeline extends Model
{
    protected $table = 'order_timeline';

    protected $fillable = ['order_id', 'status', 'note', 'user_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
