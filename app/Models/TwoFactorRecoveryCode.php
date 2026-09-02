<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class TwoFactorRecoveryCode extends Model
{
    protected $fillable = ['user_id', 'code', 'used', 'used_at'];

    protected $casts = ['used' => 'boolean', 'used_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function verify(User $user, string $code): bool
    {
        $record = static::where('user_id', $user->id)->where('used', false)->get()
            ->first(fn ($r) => Hash::check($code, $r->code));
        if ($record) {
            $record->update(['used' => true, 'used_at' => now()]);
            return true;
        }
        return false;
    }
}
