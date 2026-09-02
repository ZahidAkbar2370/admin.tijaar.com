<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

/**
 * Post-login verification gates for approved private sellers (admin toggles).
 */
class PrivateSellerVerificationService
{
    public static function requirements(): array
    {
        return [
            'email' => (string) Setting::get('private_seller_must_verify_email', '0') === '1',
            'phone' => (string) Setting::get('private_seller_must_verify_phone', '0') === '1',
            'whatsapp' => (string) Setting::get('private_seller_must_verify_whatsapp', '0') === '1',
        ];
    }

    /** Applies only to approved private sellers (is_private_seller). */
    public static function appliesTo(?User $user): bool
    {
        return $user
            && ($user->role ?? '') === 'customer'
            && (bool) ($user->is_private_seller ?? false);
    }

    /**
     * @return array{required: bool, complete: bool, missing: string[], next: ?string, requirements: array}
     */
    public static function statusFor(?User $user): array
    {
        $requirements = self::requirements();
        $applies = self::appliesTo($user);
        $missing = [];

        if ($applies) {
            if ($requirements['email'] && empty($user->email_verified_at)) {
                $missing[] = 'email';
            }
            if ($requirements['phone'] && empty($user->phone_verified_at)) {
                $missing[] = 'phone';
            }
            if ($requirements['whatsapp'] && empty($user->whatsapp_verified_at)) {
                $missing[] = 'whatsapp';
            }
        }

        $next = null;
        if (! empty($missing)) {
            $next = match ($missing[0]) {
                'email' => '/verify-otp',
                'phone' => '/customer/verify-phone',
                'whatsapp' => '/customer/verify-whatsapp',
                default => '/customer/verification',
            };
        }

        return [
            'required' => $applies && collect($requirements)->contains(true),
            'complete' => ! $applies || empty($missing),
            'missing' => $missing,
            'next' => $next,
            'requirements' => $requirements,
        ];
    }

    public static function isBlocked(?User $user): bool
    {
        $status = self::statusFor($user);

        return $status['required'] && ! $status['complete'];
    }
}
