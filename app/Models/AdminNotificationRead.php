<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotificationRead extends Model
{
    protected $fillable = ['user_id', 'type', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function markRead(int $userId, string $type): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'type' => $type],
            ['read_at' => now()]
        );
    }

    public static function unreadCount(int $userId, string $type): int
    {
        $read = static::where('user_id', $userId)->where('type', $type)->first();
        $since = $read?->read_at ?? now()->subYears(10);

        return match ($type) {
            'new_customers' => \App\Models\User::where('role', 'customer')->where('created_at', '>', $since)->count(),
            'new_sellers' => \App\Models\User::where('role', 'seller')->where('created_at', '>', $since)->count(),
            default => 0,
        };
    }
}
