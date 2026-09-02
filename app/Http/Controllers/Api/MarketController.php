<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketController extends Controller
{
    public function index(): JsonResponse
    {
        $markets = Market::where('is_active', true)->orderBy('priority')->get();
        return response()->json(['success' => true, 'markets' => $markets]);
    }

    public function current(Request $request): JsonResponse
    {
        $market = $this->detectMarket($request);
        $rate = null;
        if ($market->code === 'AE') {
            $rate = \App\Models\ExchangeRate::getCurrentRate('PKR', 'AED');
        }
        return response()->json([
            'success' => true,
            'market' => $market,
            'exchange_rate' => $rate,
        ]);
    }

    public function setPreference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'market' => 'required|in:PK,AE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid market',
                'errors' => $validator->errors(),
            ], 422);
        }

        $market = Market::where('code', $request->market)->where('is_active', true)->first();
        if (!$market) {
            return response()->json(['success' => false, 'message' => 'Market not found'], 404);
        }

        $request->user()->marketPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['market' => $request->market]
        );

        return response()->json([
            'success' => true,
            'message' => 'Market preference updated',
            'market' => $market,
        ]);
    }

    private function detectMarket(Request $request): Market
    {
        $user = $request->user();
        $preference = $user?->marketPreference?->market ?? null;
        if ($preference) {
            $market = Market::where('code', $preference)->where('is_active', true)->first();
            if ($market) return $market;
        }

        $country = $request->header('X-Country-Code') ?? $request->query('country');
        if ($country === 'AE' || $country === 'UAE') {
            return Market::where('code', 'AE')->where('is_active', true)->first()
                ?? Market::first();
        }

        return Market::where('code', 'PK')->where('is_active', true)->first()
            ?? Market::orderBy('priority')->first();
    }
}
