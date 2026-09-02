<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingRule;
use App\Models\ShippingZone;

class ShippingService
{
    /**
     * Calculate shipping cost for cart and address.
     *
     * @param float $subtotal Cart subtotal
     * @param float $weightKg Total weight in kg (0 if not tracked)
     * @param string $country Address country (Pakistan, UAE, etc.)
     * @param string $market PK or AE
     * @return array ['cost' => float, 'options' => array, 'zone' => ShippingZone|null]
     */
    public static function calculate(float $subtotal, float $weightKg, string $country, string $market = 'PK'): array
    {
        $zone = ShippingZone::active()
            ->forMarket($market)
            ->forCountry($country)
            ->orderBy('sort_order')
            ->first();

        if (!$zone) {
            return ['cost' => 0, 'options' => [['name' => 'Standard', 'cost' => 0, 'rule_id' => null]], 'zone' => null];
        }

        $rules = $zone->rules;
        $options = [];
        $lowestCost = null;

        foreach ($rules as $rule) {
            $cost = self::applyRule($rule, $subtotal, $weightKg);
            if ($cost !== null) {
                $options[] = [
                    'id' => $rule->id,
                    'name' => $rule->name ?: ucfirst(str_replace('_', ' ', $rule->type)),
                    'cost' => round($cost, 2),
                    'rule_id' => $rule->id,
                ];
                if ($lowestCost === null || $cost < $lowestCost) {
                    $lowestCost = $cost;
                }
            }
        }

        // If no rules matched, default 0 or first flat rate
        if (empty($options)) {
            $flatRule = $rules->where('type', 'flat')->first();
            $cost = $flatRule ? (float) $flatRule->rate : 0;
            $options[] = ['id' => $flatRule?->id, 'name' => 'Standard', 'cost' => $cost, 'rule_id' => $flatRule?->id];
            $lowestCost = $cost;
        }

        return [
            'cost' => round($lowestCost ?? 0, 2),
            'options' => $options,
            'zone' => $zone,
        ];
    }

    /**
     * Get shipping cost for a specific rule (e.g. customer's selected option).
     * Returns null if rule not found or not applicable to address/market.
     */
    public static function costForRule(int $ruleId, float $subtotal, float $weightKg, string $country, string $market = 'PK'): ?float
    {
        $rule = ShippingRule::where('id', $ruleId)->where('is_active', true)->with('zone')->first();
        if (!$rule || !$rule->zone || !$rule->zone->is_active) {
            return null;
        }
        $zone = $rule->zone;
        if ($zone->market !== $market) {
            return null;
        }
        $appliesToCountry = ShippingZone::where('id', $zone->id)->forCountry(trim($country))->exists();
        if (!$appliesToCountry) {
            return null;
        }
        return self::applyRule($rule, $subtotal, $weightKg);
    }

    protected static function applyRule(ShippingRule $rule, float $subtotal, float $weightKg): ?float
    {
        if ($rule->type === 'flat') {
            return (float) $rule->rate;
        }

        if ($rule->type === 'price_based') {
            if ($rule->free_threshold && $subtotal >= (float) $rule->free_threshold) {
                return 0; // Free shipping applies
            }
            if ($rule->free_threshold) {
                return null; // Free threshold rule only applies when subtotal >= threshold
            }
            if ($rule->min_order_amount && $subtotal < (float) $rule->min_order_amount) {
                return null;
            }
            return (float) $rule->rate;
        }

        if ($rule->type === 'weight_based') {
            $min = (float) ($rule->min_weight_kg ?? 0);
            $max = (float) ($rule->max_weight_kg ?? 9999);
            if ($weightKg >= $min && $weightKg <= $max) {
                return (float) $rule->rate;
            }
            return null;
        }

        return null;
    }

    /**
     * Get default shipping options for checkout (no address yet).
     */
    public static function getDefaultOptions(string $market = 'PK'): array
    {
        $zone = ShippingZone::active()
            ->forMarket($market)
            ->orderBy('sort_order')
            ->first();

        if (!$zone) {
            return [['id' => null, 'name' => 'Standard', 'cost' => 0, 'rule_id' => null]];
        }

        $options = [];
        foreach ($zone->rules as $rule) {
            $cost = $rule->type === 'flat' ? (float) $rule->rate : (float) $rule->rate;
            $options[] = [
                'id' => $rule->id,
                'name' => $rule->name ?: ucfirst(str_replace('_', ' ', $rule->type)),
                'cost' => $cost,
                'rule_id' => $rule->id,
            ];
        }

        if (empty($options)) {
            return [['id' => null, 'name' => 'Standard', 'cost' => 0, 'rule_id' => null]];
        }

        return $options;
    }
}
