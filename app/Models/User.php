<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'whatsapp_number', 'city', 'state', 'permanent_address', 'delivery_address',
        'password', 'avatar', 'avatar_alt',
        'role', 'registration_source', 'email_verified_at', 'phone_verified_at', 'whatsapp_verified_at', 'is_private_seller', 'private_listing_limit',
        'payout_hold_days', 'private_seller_kyc_status',
        'is_suspended', 'is_banned', 'abuse_score', 'preferences',
        'two_factor_enabled', 'two_factor_secret',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? \App\Support\UploadHelper::url($this->avatar) : null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_private_seller' => 'boolean',
            'is_suspended' => 'boolean',
            'is_banned' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function marketPreference()
    {
        return $this->hasOne(UserMarketPreference::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function savedCards(): HasMany
    {
        return $this->hasMany(SavedCard::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function pushNotifications(): HasMany
    {
        return $this->hasMany(PushNotification::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role || $this->roles()->where('slug', $role)->exists();
    }

    public function isActive(): bool
    {
        return !$this->is_suspended && !$this->is_banned;
    }

    /**
     * Only route mail when email is a real address (avoids empty To: header errors).
     */
    public function routeNotificationForMail($notification = null): ?string
    {
        $email = is_string($this->email) ? trim($this->email) : '';

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
