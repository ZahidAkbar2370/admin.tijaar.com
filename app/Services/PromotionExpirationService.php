<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use App\Support\HomeCache;
use Illuminate\Support\Facades\Cache;

class PromotionExpirationService
{
    /**
     * Mark overdue promotions expired and clear product flags when no other active promotion covers them.
     */
    public static function expireDue(): int
    {
        $expired = Promotion::query()
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->with('package')
            ->get();

        $count = 0;
        foreach ($expired as $promotion) {
            $promotion->update(['status' => 'expired']);
            $count++;

            if (!$promotion->product_id || !$promotion->package) {
                continue;
            }

            $type = $promotion->package->type;
            if ($type === 'featured_product') {
                $stillActive = Promotion::query()
                    ->active()
                    ->where('product_id', $promotion->product_id)
                    ->whereHas('package', fn ($q) => $q->where('type', 'featured_product'))
                    ->exists();
                if (!$stillActive) {
                    Product::where('id', $promotion->product_id)->update(['is_featured' => false]);
                }
            } elseif ($type === 'hot_sale') {
                $stillActive = Promotion::query()
                    ->active()
                    ->where('product_id', $promotion->product_id)
                    ->whereHas('package', fn ($q) => $q->where('type', 'hot_sale'))
                    ->exists();
                if (!$stillActive) {
                    Product::where('id', $promotion->product_id)->update(['is_hot' => false]);
                }
            }
        }

        if ($count > 0) {
            Cache::forget(HomeCache::KEY);
        }

        return $count;
    }
}
