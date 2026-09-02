<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationProvince;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'countries' => LocationService::publicTree(),
        ]);
    }

    public function countries(): JsonResponse
    {
        $countries = LocationCountry::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['success' => true, 'countries' => $countries]);
    }

    public function provinces(Request $request): JsonResponse
    {
        $request->validate(['country_id' => 'required|integer|exists:location_countries,id']);

        $provinces = LocationProvince::query()
            ->where('country_id', $request->integer('country_id'))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'country_id']);

        return response()->json(['success' => true, 'provinces' => $provinces]);
    }

    public function cities(Request $request): JsonResponse
    {
        $request->validate(['province_id' => 'required|integer|exists:location_provinces,id']);

        $cities = LocationCity::query()
            ->where('province_id', $request->integer('province_id'))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'leopards_city_id']);

        return response()->json(['success' => true, 'cities' => $cities]);
    }
}
