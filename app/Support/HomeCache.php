<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class HomeCache
{
    public const KEY = 'api.home.v2';

    /** @var list<string> */
    private const LEGACY_KEYS = ['api.home'];

    public static function clear(): void
    {
        Cache::forget(self::KEY);
        foreach (self::LEGACY_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
