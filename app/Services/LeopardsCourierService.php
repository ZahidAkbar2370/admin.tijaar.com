<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\LocationCity;
use App\Models\Setting;

/**
 * Leopards Courier Pakistan — tracking only.
 *
 * Sellers drop parcels at Leopards themselves and enter the CN in the seller panel.
 * Tijaar never calls getTariffDetails or bookPacket; getAllCities is kept because
 * Settings → Locations maps managed cities to Leopards city IDs.
 *
 * Credentials: Admin → Settings → Courier / Leopards
 */
class LeopardsCourierService
{
    public const PUBLIC_TRACKING_URL = 'https://pk.leopardscourier.com/tracking';

    public const PUBLIC_OFFICE_LOCATOR_URL = 'https://pk.leopardscourier.com';

    /**
     * Never expose cURL dumps, URLs, or API credentials to customers.
     */
    public static function sanitizePublicError(?string $message): string
    {
        $msg = trim((string) $message);
        if ($msg === '') {
            return 'Shipping information is temporarily unavailable. Please try again.';
        }

        $lower = strtolower($msg);
        $isTechnical = str_contains($lower, 'curl')
            || str_contains($lower, 'timed out')
            || str_contains($lower, 'timeout')
            || str_contains($lower, 'connection')
            || str_contains($lower, 'api_key')
            || str_contains($lower, 'api_password')
            || str_contains($lower, 'leopardscourier.com')
            || str_contains($lower, 'merchantapi')
            || str_contains($lower, 'http ')
            || str_contains($lower, 'ssl')
            || preg_match('/\b(500|502|503|504)\b/', $msg)
            || strlen($msg) > 160;

        if ($isTechnical) {
            return 'Shipping information is temporarily unavailable. Retrying…';
        }

        return $msg;
    }

    public static function isEnabled(): bool
    {
        return (string) Setting::get('leopards_enabled', '0') === '1';
    }

    public static function hasCredentials(array $overrides = []): bool
    {
        return (bool) (self::apiKey($overrides) && self::apiPassword($overrides));
    }

    public static function apiKey(array $overrides = []): ?string
    {
        $v = $overrides['api_key'] ?? Setting::get('leopards_api_key') ?: config('services.leopards.api_key');

        return $v !== null && $v !== '' ? (string) $v : null;
    }

    public static function apiPassword(array $overrides = []): ?string
    {
        $v = $overrides['api_password'] ?? Setting::get('leopards_api_password') ?: config('services.leopards.api_password');

        return $v !== null && $v !== '' ? (string) $v : null;
    }

    public static function baseUrl(array $overrides = []): string
    {
        return self::resolveBaseUrl($overrides)['url'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{url: string, adjusted: bool}
     */
    public static function resolveBaseUrl(array $overrides = []): array
    {
        $env = (string) ($overrides['environment'] ?? Setting::get('leopards_environment') ?? config('services.leopards.environment', 'staging'));
        if ($env !== 'production' && $env !== 'staging') {
            $env = 'staging';
        }
        $defaultUrl = rtrim((string) config(
            $env === 'production' ? 'services.leopards.production_url' : 'services.leopards.staging_url',
            $env === 'production'
                ? 'https://merchantapi.leopardscourier.com/api'
                : 'https://merchantapistaging.leopardscourier.com/api'
        ), '/');

        $custom = trim((string) ($overrides['api_url'] ?? Setting::get('leopards_api_url') ?? ''));
        if ($custom === '') {
            return ['url' => $defaultUrl, 'adjusted' => false];
        }

        $custom = rtrim($custom, '/');
        if (self::urlMatchesEnvironment($custom, $env)) {
            return ['url' => $custom, 'adjusted' => false];
        }

        return ['url' => $defaultUrl, 'adjusted' => true];
    }

    protected static function urlMatchesEnvironment(string $url, string $env): bool
    {
        $lower = strtolower($url);
        $isStagingHost = str_contains($lower, 'merchantapistaging');
        $isProductionHost = str_contains($lower, 'merchantapi.leopardscourier.com') && !$isStagingHost;

        return $env === 'production' ? $isProductionHost : $isStagingHost;
    }

    /**
     * @param  array<string, mixed>  $overrides  Optional api_key, api_password, api_url for unsaved admin form values.
     */
    public static function testConnection(array $overrides = []): array
    {
        if (!self::hasCredentials($overrides)) {
            return ['success' => false, 'message' => 'Leopards API Key and Password are required. Save settings or enter both fields, then test again.'];
        }

        try {
            $result = self::fetchAllCities(true, $overrides);
            if ($result['cities'] === null) {
                $detail = $result['error'] ?? 'Check URL and credentials.';
                $url = self::baseUrl($overrides);
                $hint = self::stagingTimeoutHint($url, $detail);

                return [
                    'success' => false,
                    'message' => 'Could not load cities from Leopards API. ' . $detail . ' (URL: ' . $url . ')' . $hint,
                ];
            }

            $resolved = self::resolveBaseUrl($overrides);
            $note = $resolved['adjusted']
                ? ' (API URL field did not match Environment — used ' . $resolved['url'] . '.)'
                : '';

            return [
                'success' => true,
                'message' => 'Connected to Leopards Courier API. ' . count($result['cities']) . ' cities available.' . $note
                    . ' Tijaar uses Leopards for tracking only — no tariff or booking calls are made.',
            ];
        } catch (\Throwable $e) {
            Log::warning('Leopards test connection failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public static function getAllCities(bool $forceRefresh = false): ?array
    {
        return self::fetchAllCities($forceRefresh)['cities'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{cities: array<int, array<string, mixed>>|null, error: string|null}
     */
    protected static function fetchAllCities(bool $forceRefresh = false, array $overrides = []): array
    {
        if (!self::hasCredentials($overrides)) {
            return ['cities' => null, 'error' => 'API key and password are required.'];
        }

        $cacheKey = 'leopards_cities_v2';
        if (!$forceRefresh && empty($overrides) && Cache::has($cacheKey)) {
            return ['cities' => Cache::get($cacheKey), 'error' => null];
        }

        $response = self::apiJsonPost('getAllCities/format/json/', [], $overrides);
        $body = $response->json() ?? [];

        if (!$response->successful()) {
            return [
                'cities' => null,
                'error' => 'HTTP ' . $response->status() . '. ' . self::formatLcsError($body, $response->body()),
            ];
        }

        if (!self::lcsStatusOk($body)) {
            return ['cities' => null, 'error' => self::formatLcsError($body)];
        }

        $list = $body['city_list'] ?? null;
        if (!is_array($list) || $list === []) {
            return ['cities' => null, 'error' => 'API returned no cities.'];
        }

        if (empty($overrides)) {
            Cache::put($cacheKey, $list, now()->addDay());
        }

        return ['cities' => $list, 'error' => null];
    }

    /**
     * Managed cities (Settings → Locations) with their Leopards city ID mapping.
     * No tariff lookups — this only verifies that IDs are linked.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{success: bool, message?: string, cities?: array<int, array<string, mixed>>, total_managed?: int, linked_leopards_ids?: int}
     */
    public static function getManagedCityMappings(array $overrides = [], bool $refresh = false): array
    {
        if (!self::hasCredentials($overrides)) {
            return ['success' => false, 'message' => 'Leopards API Key and Password are required.'];
        }

        $managedCities = LocationCity::query()
            ->where('is_active', true)
            ->with('province:id,name')
            ->orderBy('name')
            ->get();

        if ($managedCities->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No cities in Settings → Locations. Add cities there first, then use Sync Leopards IDs.',
            ];
        }

        $result = self::fetchAllCities($refresh, $overrides);
        if ($result['cities'] === null) {
            return [
                'success' => false,
                'message' => 'Could not load cities from Leopards API. ' . ($result['error'] ?? 'Check URL and credentials.'),
            ];
        }

        $rows = [];
        foreach ($managedCities as $city) {
            $cityName = trim($city->name);
            $leopardsId = $city->leopards_city_id ?: LocationService::findLeopardsCityMatch($cityName, $result['cities']);

            $rows[] = [
                'city_id' => $leopardsId ?: $cityName,
                'city_name' => $cityName,
                'province' => $city->province?->name,
                'leopards_city_id' => $city->leopards_city_id,
                'linked' => (bool) $city->leopards_city_id,
            ];
        }

        usort($rows, fn (array $a, array $b) => strcasecmp($a['city_name'], $b['city_name']));

        return [
            'success' => true,
            'total_managed' => $managedCities->count(),
            'linked_leopards_ids' => $managedCities->filter(fn ($c) => !empty($c->leopards_city_id))->count(),
            'cities' => $rows,
        ];
    }

    /**
     * @return array{status: string, raw: array}|null
     */
    public static function trackShipment(string $trackingNumber): ?array
    {
        if (!self::hasCredentials() || !$trackingNumber) {
            return null;
        }

        $response = self::apiJsonPost('trackBookedPacket/format/json/', [
            'track_numbers' => $trackingNumber,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $body = $response->json() ?? [];
        if (!self::lcsStatusOk($body)) {
            return null;
        }
        $status = $body['packet_list'][0]['booked_packet_status']
            ?? $body['status']
            ?? $body['tracking_status']
            ?? null;

        return $status ? ['status' => (string) $status, 'raw' => $body] : null;
    }

    public static function mapTrackingStatus(string $lcsStatus): ?string
    {
        $s = strtolower(trim($lcsStatus));
        // Returns / failed attempts stay unmapped so the shipment keeps polling
        // and is never mistaken for a completed delivery.
        if (str_contains($s, 'not deliver')
            || str_contains($s, 'undeliver')
            || str_contains($s, 'return')
            || str_contains($s, 'attempt')) {
            return null;
        }

        if (str_contains($s, 'deliver')) {
            return 'delivered';
        }
        if (str_contains($s, 'transit') || str_contains($s, 'route') || str_contains($s, 'out for')) {
            return 'in_transit';
        }
        if (str_contains($s, 'ship') || str_contains($s, 'pick') || str_contains($s, 'manifest')) {
            return 'shipped';
        }
        if (str_contains($s, 'book') || str_contains($s, 'pending')) {
            return 'booked';
        }

        return null;
    }

    public static function getTrackingUrl(?string $trackingNumber): ?string
    {
        if (!$trackingNumber) {
            return null;
        }

        return self::PUBLIC_TRACKING_URL;
    }

    /** Replace legacy per-CN URLs stored before the official portal link was used. */
    public static function normalizeTrackingUrl(?string $url, ?string $trackingNumber): ?string
    {
        if (!$trackingNumber) {
            return $url;
        }

        if ($url && !str_contains(strtolower($url), 'leopardscourier.com/track/')) {
            return $url;
        }

        return self::getTrackingUrl($trackingNumber);
    }

    protected static function lcsStatusOk(array $body): bool
    {
        $status = $body['status'] ?? null;

        return $status === 1 || $status === '1' || $status === true;
    }

    protected static function stagingTimeoutHint(string $url, string $detail): string
    {
        $isStagingHost = str_contains(strtolower($url), 'merchantapistaging');
        $isGatewayTimeout = str_contains($detail, '504') || str_contains(strtolower($detail), 'gateway time-out');

        if (!$isStagingHost || !$isGatewayTimeout) {
            return '';
        }

        return ' Leopards staging/sandbox server is not responding (504). This is on Leopards\' side — not your credentials. '
            . 'Switch Environment to Production and API URL to https://merchantapi.leopardscourier.com/api, save, then test again.';
    }

    protected static function formatLcsError(array $body, ?string $rawBody = null): string
    {
        $error = $body['error'] ?? null;
        if (is_string($error) && $error !== '' && $error !== '0') {
            return $error;
        }
        if (is_array($error) && $error !== []) {
            return json_encode($error, JSON_UNESCAPED_UNICODE) ?: 'API returned an error.';
        }
        if ($rawBody) {
            $trimmed = trim(strip_tags($rawBody));
            if ($trimmed !== '' && strlen($trimmed) < 300) {
                return $trimmed;
            }
        }

        return 'Unexpected API response.';
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected static function apiJsonPost(string $path, array $extra = [], array $overrides = [])
    {
        $payload = array_merge([
            'api_key' => self::apiKey($overrides),
            'api_password' => self::apiPassword($overrides),
        ], $extra);

        return Http::timeout(25)
            ->acceptJson()
            ->asJson()
            ->post(self::endpoint($path, $overrides), $payload);
    }

    protected static function endpoint(string $path, array $overrides = []): string
    {
        return self::baseUrl($overrides) . '/' . ltrim($path, '/');
    }
}
