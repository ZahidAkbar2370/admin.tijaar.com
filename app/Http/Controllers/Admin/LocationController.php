<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationProvince;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function countries(): JsonResponse
    {
        $countries = LocationCountry::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'countries' => $countries]);
    }

    public function storeCountry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:location_countries,name',
            'code' => 'nullable|string|max:8',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $country = LocationCountry::create([
            'name' => trim($data['name']),
            'code' => isset($data['code']) ? trim($data['code']) : null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'country' => $country]);
    }

    public function updateCountry(Request $request, LocationCountry $country): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:location_countries,name,' . $country->id,
            'code' => 'nullable|string|max:8',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $country->update([
            'name' => trim($data['name']),
            'code' => isset($data['code']) ? trim($data['code']) : null,
            'is_active' => $data['is_active'] ?? $country->is_active,
            'sort_order' => $data['sort_order'] ?? $country->sort_order,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'country' => $country->fresh()]);
    }

    public function destroyCountry(LocationCountry $country): JsonResponse
    {
        $country->delete();
        LocationService::forgetCache();

        return response()->json(['success' => true]);
    }

    public function provinces(Request $request): JsonResponse
    {
        $query = LocationProvince::query()->with('country:id,name')->orderBy('sort_order')->orderBy('name');
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->integer('country_id'));
        }

        return response()->json(['success' => true, 'provinces' => $query->get()]);
    }

    public function storeProvince(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => 'required|integer|exists:location_countries,id',
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $exists = LocationProvince::query()
            ->where('country_id', $data['country_id'])
            ->where('name', trim($data['name']))
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Province already exists for this country.'], 422);
        }

        $province = LocationProvince::create([
            'country_id' => $data['country_id'],
            'name' => trim($data['name']),
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'province' => $province->load('country:id,name')]);
    }

    public function updateProvince(Request $request, LocationProvince $province): JsonResponse
    {
        $data = $request->validate([
            'country_id' => 'required|integer|exists:location_countries,id',
            'name' => 'required|string|max:120',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $duplicate = LocationProvince::query()
            ->where('country_id', $data['country_id'])
            ->where('name', trim($data['name']))
            ->where('id', '!=', $province->id)
            ->exists();
        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'Province already exists for this country.'], 422);
        }

        $province->update([
            'country_id' => $data['country_id'],
            'name' => trim($data['name']),
            'is_active' => $data['is_active'] ?? $province->is_active,
            'sort_order' => $data['sort_order'] ?? $province->sort_order,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'province' => $province->fresh()->load('country:id,name')]);
    }

    public function destroyProvince(LocationProvince $province): JsonResponse
    {
        $province->delete();
        LocationService::forgetCache();

        return response()->json(['success' => true]);
    }

    public function cities(Request $request): JsonResponse
    {
        $query = LocationCity::query()
            ->with('province:id,name,country_id', 'province.country:id,name')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        return response()->json(['success' => true, 'cities' => $query->get()]);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => 'required|integer|exists:location_provinces,id',
            'name' => 'required|string|max:120',
            'leopards_city_id' => 'nullable|string|max:32',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $exists = LocationCity::query()
            ->where('province_id', $data['province_id'])
            ->where('name', trim($data['name']))
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'City already exists for this province.'], 422);
        }

        $city = LocationCity::create([
            'province_id' => $data['province_id'],
            'name' => trim($data['name']),
            'leopards_city_id' => isset($data['leopards_city_id']) ? trim((string) $data['leopards_city_id']) : null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'city' => $city->load('province.country')]);
    }

    public function updateCity(Request $request, LocationCity $city): JsonResponse
    {
        $data = $request->validate([
            'province_id' => 'required|integer|exists:location_provinces,id',
            'name' => 'required|string|max:120',
            'leopards_city_id' => 'nullable|string|max:32',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $duplicate = LocationCity::query()
            ->where('province_id', $data['province_id'])
            ->where('name', trim($data['name']))
            ->where('id', '!=', $city->id)
            ->exists();
        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'City already exists for this province.'], 422);
        }

        $city->update([
            'province_id' => $data['province_id'],
            'name' => trim($data['name']),
            'leopards_city_id' => array_key_exists('leopards_city_id', $data)
                ? (trim((string) ($data['leopards_city_id'] ?? '')) ?: null)
                : $city->leopards_city_id,
            'is_active' => $data['is_active'] ?? $city->is_active,
            'sort_order' => $data['sort_order'] ?? $city->sort_order,
        ]);

        LocationService::forgetCache();

        return response()->json(['success' => true, 'city' => $city->fresh()->load('province.country')]);
    }

    public function destroyCity(LocationCity $city): JsonResponse
    {
        $city->delete();
        LocationService::forgetCache();

        return response()->json(['success' => true]);
    }

    public function importLeopardsCities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => 'required|integer|exists:location_provinces,id',
        ]);

        $result = LocationService::importCitiesFromLeopards($data['province_id']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function syncLeopardsIds(): JsonResponse
    {
        $result = LocationService::syncLeopardsCityIds();

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
