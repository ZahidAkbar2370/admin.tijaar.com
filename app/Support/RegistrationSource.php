<?php

namespace App\Support;

use Illuminate\Http\Request;

class RegistrationSource
{
    public const WEB = 'web';

    public const APP = 'app';

    public const API = 'api';

    /** @var list<string> */
    public const VALUES = [self::WEB, self::APP, self::API];

    /**
     * Resolve where a customer registered: web storefront, mobile app, or external API.
     */
    public static function fromRequest(Request $request): string
    {
        $explicit = $request->input('registration_source')
            ?? $request->header('X-Registration-Source');

        if (is_string($explicit) && in_array(strtolower($explicit), self::VALUES, true)) {
            return strtolower($explicit);
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua !== '' && (
            str_contains($ua, 'dart')
            || str_contains($ua, 'okhttp')
            || str_contains($ua, 'flutter')
        )) {
            return self::APP;
        }

        return self::WEB;
    }

    public static function label(?string $source): string
    {
        return match ($source) {
            self::APP => 'App',
            self::API => 'API',
            default => 'Web',
        };
    }
}
