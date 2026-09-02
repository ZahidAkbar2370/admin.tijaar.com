<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = ['code', 'name', 'currency_code', 'currency_symbol', 'priority', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
