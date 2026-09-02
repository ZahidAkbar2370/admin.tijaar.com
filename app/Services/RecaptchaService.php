<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    public static function isEnabled(): bool
    {
        return (string) Setting::get('recaptcha_enabled', '0') === '1'
            && self::siteKey() !== ''
            && self::secretKey() !== '';
    }

    public static function requiredForLogin(): bool
    {
        return self::isEnabled() && (string) Setting::get('recaptcha_on_login', '1') === '1';
    }

    public static function requiredForRegister(): bool
    {
        return self::isEnabled() && (string) Setting::get('recaptcha_on_register', '1') === '1';
    }

    public static function siteKey(): string
    {
        return trim((string) Setting::get('recaptcha_site_key', config('settings_defaults.recaptcha_site_key', '')));
    }

    public static function secretKey(): string
    {
        return trim((string) Setting::get('recaptcha_secret_key', config('settings_defaults.recaptcha_secret_key', '')));
    }

    /**
     * Verify a Google reCAPTCHA v2 response token.
     *
     * @return array{ok: bool, message?: string}
     */
    public static function verify(?string $token, ?string $remoteIp = null): array
    {
        $token = is_string($token) ? trim($token) : '';
        if ($token === '') {
            return ['ok' => false, 'message' => 'Please complete the reCAPTCHA challenge.'];
        }

        $secret = self::secretKey();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'reCAPTCHA is not configured. Please contact support.'];
        }

        try {
            $payload = [
                'secret' => $secret,
                'response' => $token,
            ];
            if ($remoteIp) {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', $payload);

            $body = $response->json();
            if (!is_array($body)) {
                return ['ok' => false, 'message' => 'Could not verify reCAPTCHA. Please try again.'];
            }

            if (!empty($body['success'])) {
                return ['ok' => true];
            }

            Log::info('reCAPTCHA verification failed', [
                'error-codes' => $body['error-codes'] ?? [],
            ]);

            return ['ok' => false, 'message' => 'reCAPTCHA verification failed. Please try again.'];
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA request error: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Could not verify reCAPTCHA. Please try again.'];
        }
    }

    /**
     * Public config for the storefront (never includes the secret).
     */
    public static function publicConfig(): array
    {
        $enabled = self::isEnabled();

        return [
            'recaptcha_enabled' => $enabled,
            'recaptcha_site_key' => $enabled ? self::siteKey() : '',
            'recaptcha_on_login' => $enabled && (string) Setting::get('recaptcha_on_login', '1') === '1',
            'recaptcha_on_register' => $enabled && (string) Setting::get('recaptcha_on_register', '1') === '1',
        ];
    }
}
