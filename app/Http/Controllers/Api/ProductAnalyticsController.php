<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductAnalyticsController extends Controller
{
    /**
     * Track product engagement: impression | click | share.
     * Light per-session dedupe to avoid double-counting refreshes.
     */
    public function track(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'event' => 'required|in:impression,click,share',
        ]);

        $product = Product::query()->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $event = $request->input('event');
        $sessionKey = $request->header('X-Session-ID')
            ?: $request->ip()
            ?: 'anon';
        $cacheKey = "product_analytics:{$productId}:{$event}:" . md5((string) $sessionKey);
        $ttlMinutes = $event === 'impression' ? 30 : 60;

        if (!Cache::add($cacheKey, 1, now()->addMinutes($ttlMinutes))) {
            return response()->json([
                'success' => true,
                'deduped' => true,
                'impressions_count' => (int) $product->impressions_count,
                'clicks_count' => (int) $product->clicks_count,
                'shares_count' => (int) $product->shares_count,
            ]);
        }

        $column = match ($event) {
            'impression' => 'impressions_count',
            'click' => 'clicks_count',
            'share' => 'shares_count',
        };
        $product->increment($column);
        $product->refresh();

        return response()->json([
            'success' => true,
            'deduped' => false,
            'impressions_count' => (int) $product->impressions_count,
            'clicks_count' => (int) $product->clicks_count,
            'shares_count' => (int) $product->shares_count,
        ]);
    }
}
