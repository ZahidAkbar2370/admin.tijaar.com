<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Persist an activity row. Never throws — logging must not break the app.
     *
     * @param  array{
     *     action_type: string,
     *     action_by?: int|null,
     *     target_table?: string|null,
     *     action_on?: string|int|null,
     *     description?: string|null,
     *     device?: string|null,
     *     ip_address?: string|null,
     *     location?: string|null,
     * }  $data
     */
    public static function log(array $data, ?Request $request = null): ?Activity
    {
        try {
            $request = $request ?? request();

            $actionBy = $data['action_by'] ?? null;
            if ($actionBy === null && $request) {
                $actionBy = optional($request->user())->id ?? auth()->id();
            }

            $actionOn = $data['action_on'] ?? null;
            if ($actionOn !== null && $actionOn !== '') {
                $actionOn = (string) $actionOn;
            } else {
                $actionOn = null;
            }

            $activity = Activity::create([
                'action_by' => $actionBy ?: null,
                'target_table' => self::truncate($data['target_table'] ?? null, 100),
                'action_type' => self::truncate((string) ($data['action_type'] ?? 'other'), 64) ?: 'other',
                'action_on' => self::truncate($actionOn, 64),
                'description' => $data['description'] ?? null,
                'device' => self::truncate($data['device'] ?? self::detectDevice($request), 120),
                'ip_address' => self::truncate($data['ip_address'] ?? ($request?->ip()), 45),
                'location' => self::truncate($data['location'] ?? self::resolveLocation($request), 255),
            ]);

            if ($request) {
                $request->attributes->set('activity_logged', true);
            }

            return $activity;
        } catch (\Throwable $e) {
            Log::warning('ActivityLogger failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Infer and log from a successful mutating HTTP request (API / admin).
     */
    public static function logFromRequest(Request $request, ?string $overrideActionType = null, ?string $description = null): ?Activity
    {
        [$actionType, $targetTable, $actionOn] = self::inferFromRequest($request);

        if ($overrideActionType) {
            $actionType = $overrideActionType;
        }

        if (!$description) {
            $description = self::defaultDescription($request, $actionType, $targetTable, $actionOn);
        }

        return self::log([
            'action_type' => $actionType,
            'target_table' => $targetTable,
            'action_on' => $actionOn,
            'description' => $description,
        ], $request);
    }

    /**
     * @return array{0: string, 1: string|null, 2: string|null}
     */
    public static function inferFromRequest(Request $request): array
    {
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());

        // Strip api/v1 prefix for matching
        $apiPath = preg_replace('#^api/v1/#', '', $path) ?? $path;
        $apiPath = preg_replace('#^admin/#', '', $apiPath) ?? $apiPath;

        $special = self::specialActionMap($apiPath, $method, $request);
        if ($special) {
            return $special;
        }

        $actionType = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'other',
        };

        [$targetTable, $actionOn] = self::guessTarget($apiPath, $request);

        return [$actionType, $targetTable, $actionOn];
    }

    /**
     * @return array{0: string, 1: string|null, 2: string|null}|null
     */
    private static function specialActionMap(string $path, string $method, Request $request): ?array
    {
        $path = strtolower($path);

        if ($method === 'POST' && $path === 'register') {
            return ['register', 'users', null];
        }
        if ($method === 'POST' && $path === 'login') {
            return ['login', 'users', null];
        }
        if ($method === 'POST' && $path === 'logout') {
            return ['logout', 'users', (string) (optional($request->user())->id ?? '')];
        }
        if ($method === 'POST' && $path === 'verify-otp') {
            return ['verify_email', 'users', null];
        }
        if ($method === 'POST' && preg_match('#^orders$#', $path)) {
            return ['place_order', 'orders', null];
        }
        if ($method === 'POST' && preg_match('#^orders/(\d+)/cancel#', $path, $m)) {
            return ['cancel_order', 'orders', $m[1]];
        }
        if ($method === 'POST' && preg_match('#^orders/(\d+)/request-cancellation#', $path, $m)) {
            return ['cancel_order', 'orders', $m[1]];
        }
        if (str_contains($path, 'webhook') && str_contains($path, 'jazzcash')) {
            return ['payment_success', 'orders', null];
        }
        if (str_contains($path, 'webhook') && str_contains($path, 'stripe')) {
            return ['payment_success', 'orders', null];
        }
        if (str_contains($path, 'webhook') && str_contains($path, 'easypaisa')) {
            return ['payment_success', 'orders', null];
        }
        if ($method === 'POST' && str_contains($path, 'settings') && ! str_contains($path, 'test-email')) {
            return ['settings_update', 'settings', null];
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private static function guessTarget(string $path, Request $request): array
    {
        $segments = array_values(array_filter(explode('/', $path)));
        if ($segments === []) {
            return [null, null];
        }

        // Prefer route parameters that look like models
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                $table = method_exists($value, 'getTable') ? $value->getTable() : Str::plural($key);

                return [$table, (string) $value->getKey()];
            }
            if (is_numeric($value) || (is_string($value) && ctype_digit($value))) {
                $resource = Str::plural(Str::snake((string) $key));

                return [$resource, (string) $value];
            }
        }

        // First path segment as resource table guess
        $resource = $segments[0];
        $resource = str_replace('-', '_', $resource);
        $id = null;
        if (isset($segments[1]) && ctype_digit($segments[1])) {
            $id = $segments[1];
        }

        $map = [
            'user' => 'users',
            'users' => 'users',
            'seller' => 'sellers',
            'sellers' => 'sellers',
            'product' => 'products',
            'products' => 'products',
            'order' => 'orders',
            'orders' => 'orders',
            'category' => 'categories',
            'categories' => 'categories',
            'brand' => 'brands',
            'brands' => 'brands',
            'coupon' => 'coupons',
            'coupons' => 'coupons',
            'address' => 'addresses',
            'addresses' => 'addresses',
            'cart' => 'carts',
            'wishlist' => 'wishlists',
            'review' => 'reviews',
            'reviews' => 'reviews',
            'profile' => 'users',
            'wallet' => 'wallets',
            'payout' => 'payouts',
            'payouts' => 'payouts',
            'refund' => 'refunds',
            'refunds' => 'refunds',
            'dispute' => 'disputes',
            'disputes' => 'disputes',
            'private_listing' => 'products',
            'listings' => 'products',
            'seller_products' => 'products',
        ];

        $table = $map[$resource] ?? $resource;

        return [$table, $id];
    }

    private static function defaultDescription(Request $request, string $actionType, ?string $table, ?string $actionOn): string
    {
        $who = optional($request->user())->email ?? optional($request->user())->name ?? 'Guest';
        $parts = [ucfirst(str_replace('_', ' ', $actionType))];
        if ($table) {
            $parts[] = 'on '.$table;
        }
        if ($actionOn) {
            $parts[] = '#'.$actionOn;
        }
        $parts[] = 'by '.$who;
        $parts[] = '('.$request->method().' '.$request->path().')';

        return implode(' ', $parts);
    }

    public static function detectDevice(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') {
            return 'Unknown';
        }

        if (str_contains($ua, 'tijaar') || str_contains($ua, 'okhttp') || str_contains($ua, 'dart')) {
            return 'Mobile App';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            if (str_contains($ua, 'chrome')) {
                return 'Mobile Web (Chrome)';
            }
            if (str_contains($ua, 'safari')) {
                return 'Mobile Web (Safari)';
            }

            return 'Mobile Web';
        }
        if (str_contains($ua, 'edg/')) {
            return 'Web (Edge)';
        }
        if (str_contains($ua, 'chrome')) {
            return 'Web (Chrome)';
        }
        if (str_contains($ua, 'firefox')) {
            return 'Web (Firefox)';
        }
        if (str_contains($ua, 'safari')) {
            return 'Web (Safari)';
        }

        return 'Web';
    }

    public static function resolveLocation(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        // CDN / proxy headers first (no external call)
        $country = $request->header('CF-IPCountry')
            ?: $request->header('X-Country-Code')
            ?: $request->header('CloudFront-Viewer-Country');

        $city = $request->header('CF-IPCity')
            ?: $request->header('X-City');

        if ($city || $country) {
            $parts = array_filter([(string) $city, (string) $country]);

            return $parts ? implode(', ', $parts) : null;
        }

        $ip = $request->ip();
        if (! $ip || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Local';
        }

        // Soft geo lookup (cached 24h). Failures are ignored.
        try {
            return Cache::remember('activity_geo_'.$ip, 86400, function () use ($ip) {
                $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
                $json = @file_get_contents('http://ip-api.com/json/'.urlencode($ip).'?fields=status,country,city', false, $ctx);
                if (! $json) {
                    return null;
                }
                $data = json_decode($json, true);
                if (($data['status'] ?? '') !== 'success') {
                    return null;
                }
                $parts = array_filter([$data['city'] ?? null, $data['country'] ?? null]);

                return $parts ? implode(', ', $parts) : null;
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function truncate(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
