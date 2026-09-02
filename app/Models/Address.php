<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'type', 'label', 'first_name', 'last_name', 'phone',
        'address_line_1', 'address_line_2', 'city', 'state', 'country', 'zip_code',
        'is_default'
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
