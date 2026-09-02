<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionDisplayHelper
{
    /**
     * @return array<int, string> product_id => promotion package type (featured_product|hot_sale)
     */
    public static function activeProductPromotionTypes(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = Promotion::query()
            ->active()
            ->whereIn('product_id', $productIds)
            ->whereHas('package', fn ($q) => $q->whereIn('type', ['featured_product', 'hot_sale']))
            ->with('package:id,type')
            ->get(['id', 'product_id', 'promotion_package_id']);

        $map = [];
        foreach ($rows as $row) {
            if ($row->product_id && $row->package?->type) {
                $map[(int) $row->product_id] = $row->package->type;
            }
        }

        return $map;
    }

    public static function attachPromotionFields(array $item, ?string $promotionType): array
    {
        $item['promotion_type'] = $promotionType;
        if ($promotionType === 'featured_product') {
            $item['is_featured'] = true;
            $item['is_hot'] = false;
            $item['show_promotion_diamond'] = true;
            $item['show_hot_deal'] = false;
        } elseif ($promotionType === 'hot_sale') {
            $item['is_featured'] = false;
            $item['is_hot'] = true;
            $item['show_promotion_diamond'] = false;
            $item['show_hot_deal'] = true;
        } else {
            $item['is_featured'] = false;
            $item['is_hot'] = false;
            $item['show_promotion_diamond'] = false;
            $item['show_hot_deal'] = false;
        }

        return $item;
    }
}
