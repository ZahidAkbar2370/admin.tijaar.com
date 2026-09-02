<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMarketPreference extends Model
{
    protected $table = 'user_market_preferences';

    protected $fillable = ['user_id', 'market'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
