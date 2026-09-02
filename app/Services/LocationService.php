<?php

namespace App\Services;

use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationProvince;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LocationService
{
    public const CACHE_KEY = 'locations.public.v1';

    public const DEFAULT_COUNTRY = 'Pakistan';

    public static function defaultCountryName(): string
    {
        return self::DEFAULT_COUNTRY;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withDefaultCountry(array $data): array
    {
        if (! array_key_exists('country', $data)) {
            return $data;
        }

        if (trim((string) $data['country']) === '') {
            $data['country'] = self::defaultCountryName();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function ensureCountry(array $data): array
    {
        if (trim((string) ($data['country'] ?? '')) === '') {
            $data['country'] = self::defaultCountryName();
        }

        return $data;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function publicTree(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            return LocationCountry::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with([
                    'provinces' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name'),
                    'provinces.cities' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('name'),
                ])
                ->get()
                ->map(fn (LocationCountry $country) => [
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->code,
                    'provinces' => $country->provinces->map(fn (LocationProvince $province) => [
                        'id' => $province->id,
                        'name' => $province->name,
                        'country_id' => $province->country_id,
                        'cities' => $province->cities->map(fn (LocationCity $city) => [
                            'id' => $city->id,
                            'name' => $city->name,
                            'province_id' => $city->province_id,
                            'leopards_city_id' => $city->leopards_city_id,
                        ])->values()->all(),
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });
    }

    public static function resolveLeopardsCityId(string $cityName): int|string|null
    {
        $normalized = self::normalizeCityKey($cityName);
        if ($normalized === '') {
            return null;
        }

        $city = LocationCity::query()
            ->where('is_active', true)
            ->whereNotNull('leopards_city_id')
            ->where('leopards_city_id', '!=', '')
            ->get()
            ->first(fn (LocationCity $row) => self::normalizeCityKey($row->name) === $normalized);

        if ($city) {
            return $city->leopards_city_id;
        }

        return null;
    }

    public static function normalizeCityKey(string $name): string
    {
        $key = strtolower(trim($name));
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;
        $key = str_replace(['.', ',', '-', '_'], ' ', $key);
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;

        return trim($key);
    }

    /**
     * @param  array<int, array<string, mixed>>  $list
     */
    public static function findLeopardsCityMatch(string $cityName, array $list): ?string
    {
        $key = self::normalizeCityKey($cityName);
        if ($key === '') {
            return null;
        }

        $byKey = [];
        foreach ($list as $row) {
            $apiName = (string) ($row['city_name'] ?? $row['name'] ?? $row['City_Name'] ?? $row['city'] ?? '');
            $apiKey = self::normalizeCityKey($apiName);
            $leopardsId = $row['city_id'] ?? $row['id'] ?? $row['City_ID'] ?? null;
            if ($apiKey !== '' && $leopardsId !== null) {
                $byKey[$apiKey] = (string) $leopardsId;
            }
        }

        if (isset($byKey[$key])) {
            return $byKey[$key];
        }

        $prefixMatches = [];
        foreach ($byKey as $apiKey => $leopardsId) {
            if (str_starts_with($apiKey, $key) || str_starts_with($key, $apiKey)) {
                $prefixMatches[$apiKey] = $leopardsId;
            }
        }

        if (count($prefixMatches) === 1) {
            return (string) reset($prefixMatches);
        }

        return null;
    }

    /**
     * Match Leopards API city IDs to existing location_cities by name (no bulk import).
     *
     * @return array{success: bool, message: string, updated: int, skipped: int, already_linked: int, unmatched: list<string>}
     */
    public static function syncLeopardsCityIds(): array
    {
        $list = LeopardsCourierService::getAllCities(true);
        if (!$list) {
            return [
                'success' => false,
                'message' => 'Could not load cities from Leopards API. Check credentials in Leopards settings.',
                'updated' => 0,
                'skipped' => 0,
                'already_linked' => 0,
                'unmatched' => [],
            ];
        }

        $updated = 0;
        $skipped = 0;
        $alreadyLinked = 0;
        $unmatched = [];

        LocationCity::query()->where('is_active', true)->each(function (LocationCity $city) use ($list, &$updated, &$skipped, &$alreadyLinked, &$unmatched) {
            $leopardsId = self::findLeopardsCityMatch($city->name, $list);
            if ($leopardsId === null) {
                $skipped++;
                $unmatched[] = $city->name;

                return;
            }

            if ((string) $city->leopards_city_id === $leopardsId) {
                $alreadyLinked++;

                return;
            }

            $city->update(['leopards_city_id' => $leopardsId]);
            $updated++;
        });

        self::forgetCache();

        $message = "Newly linked {$updated} cities.";
        if ($alreadyLinked > 0) {
            $message .= " {$alreadyLinked} already had the correct Leopards ID.";
        }
        if ($skipped > 0) {
            $names = implode(', ', array_slice($unmatched, 0, 8));
            $suffix = count($unmatched) > 8 ? '…' : '';
            $message .= " {$skipped} could not be matched in the Leopards city list: {$names}{$suffix}.";
        }

        return [
            'success' => true,
            'message' => trim($message),
            'updated' => $updated,
            'skipped' => $skipped,
            'already_linked' => $alreadyLinked,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Import Leopards API cities into a province (create/update by name).
     *
     * @return array{success: bool, message: string, created: int, updated: int}
     */
    public static function importCitiesFromLeopards(int $provinceId): array
    {
        $province = LocationProvince::with('country')->find($provinceId);
        if (!$province) {
            return ['success' => false, 'message' => 'Province not found.', 'created' => 0, 'updated' => 0];
        }

        $list = LeopardsCourierService::getAllCities(true);
        if (!$list) {
            return [
                'success' => false,
                'message' => 'Could not load cities from Leopards API. Check credentials in Leopards settings.',
                'created' => 0,
                'updated' => 0,
            ];
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($list, $provinceId, &$created, &$updated) {
            foreach ($list as $row) {
                $name = trim((string) ($row['city_name'] ?? $row['name'] ?? ''));
                $leopardsId = $row['city_id'] ?? $row['id'] ?? null;
                if ($name === '' || $leopardsId === null) {
                    continue;
                }

                $city = LocationCity::query()->firstOrNew([
                    'province_id' => $provinceId,
                    'name' => $name,
                ]);

                $city->leopards_city_id = (string) $leopardsId;
                $city->is_active = true;
                $city->save();

                if ($city->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        self::forgetCache();

        return [
            'success' => true,
            'message' => "Imported {$created} new and updated {$updated} existing cities under {$province->name}.",
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public static function seedDefaults(): void
    {
        if (LocationCountry::query()->exists()) {
            return;
        }

        $pakistan = LocationCountry::create([
            'name' => 'Pakistan',
            'code' => 'PK',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $provinces = [
            'Punjab' => ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala'],
            'Sindh' => ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana'],
            'Khyber Pakhtunkhwa' => ['Peshawar', 'Mardan', 'Abbottabad'],
            'Balochistan' => ['Quetta', 'Gwadar'],
            'Islamabad Capital Territory' => ['Islamabad'],
        ];

        $order = 0;
        foreach ($provinces as $provinceName => $cities) {
            $order++;
            $province = LocationProvince::create([
                'country_id' => $pakistan->id,
                'name' => $provinceName,
                'is_active' => true,
                'sort_order' => $order,
            ]);

            $cityOrder = 0;
            foreach ($cities as $cityName) {
                $cityOrder++;
                LocationCity::create([
                    'province_id' => $province->id,
                    'name' => $cityName,
                    'is_active' => true,
                    'sort_order' => $cityOrder,
                ]);
            }
        }

        self::forgetCache();
    }
}
