<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    public const TYPE_ORDER = 'order';

    public const TYPE_LISTING = 'listing';

    public const TYPE_MESSAGE = 'message';

    public const TYPE_PROMOTION = 'promotion';

    public const TYPES = [
        self::TYPE_ORDER,
        self::TYPE_LISTING,
        self::TYPE_MESSAGE,
        self::TYPE_PROMOTION,
    ];

    /** User-facing notification channels (stored in DB). */
    public const CHANNELS = [
        'email',
        'whatsapp',
        'push_web',
        'push_app',
    ];

    protected $fillable = ['user_id', 'channel', 'type', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaults(): array
    {
        $rows = [];
        foreach (self::TYPES as $type) {
            $rows[] = ['channel' => 'email', 'type' => $type, 'enabled' => $type !== self::TYPE_PROMOTION];
            $rows[] = ['channel' => 'whatsapp', 'type' => $type, 'enabled' => $type !== self::TYPE_PROMOTION];
            // Firebase browser + mobile app alerts — enabled by default for all users.
            $rows[] = ['channel' => 'push_web', 'type' => $type, 'enabled' => true];
            $rows[] = ['channel' => 'push_app', 'type' => $type, 'enabled' => true];
        }

        return $rows;
    }

    public static function channelLabel(string $channel): string
    {
        return match ($channel) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'push_web' => 'Website (browser alerts)',
            'push_app' => 'Mobile App',
            'push', 'fcm' => 'Push',
            default => ucfirst(str_replace('_', ' ', $channel)),
        };
    }

    /** Channels shown in admin/user preference UIs (respects WhatsApp site setting). */
    public static function uiChannels(bool $whatsappChannelOn = true): array
    {
        return array_values(array_filter(self::CHANNELS, function (string $ch) use ($whatsappChannelOn) {
            if ($ch === 'whatsapp' && ! $whatsappChannelOn) {
                return false;
            }

            return true;
        }));
    }

    /** Map FCM device_type to preference channel. */
    public static function channelForDeviceType(string $deviceType): string
    {
        return match (strtolower(trim($deviceType))) {
            'android', 'ios' => 'push_app',
            default => 'push_web',
        };
    }

    /** Whether user opted in for a channel+type (missing row = enabled). */
    public static function userAllows(int $userId, string $channel, string $type): bool
    {
        $pref = static::query()
            ->where('user_id', $userId)
            ->where('channel', $channel)
            ->where('type', $type)
            ->first();

        if (! $pref) {
            return true;
        }

        return (bool) $pref->enabled;
    }

    /** Seed missing default rows for a user. */
    public static function seedDefaultsForUser(int $userId, bool $whatsappChannelOn = true): void
    {
        foreach (self::defaults() as $d) {
            if ($d['channel'] === 'whatsapp' && ! $whatsappChannelOn) {
                continue;
            }
            static::firstOrCreate(
                ['user_id' => $userId, 'channel' => $d['channel'], 'type' => $d['type']],
                ['enabled' => $d['enabled']]
            );
        }
    }
}
