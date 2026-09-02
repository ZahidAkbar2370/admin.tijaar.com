<?php

namespace Database\Seeders;

use App\Models\ShippingRule;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        $pakistan = ShippingZone::firstOrCreate(
            ['market' => 'PK', 'country' => 'Pakistan'],
            ['name' => 'Pakistan', 'sort_order' => 1, 'is_active' => true]
        );
        ShippingRule::firstOrCreate(
            ['shipping_zone_id' => $pakistan->id, 'type' => 'flat'],
            ['name' => 'Standard Delivery', 'rate' => 200, 'sort_order' => 1, 'is_active' => true]
        );
        ShippingRule::firstOrCreate(
            ['shipping_zone_id' => $pakistan->id, 'type' => 'price_based'],
            ['name' => 'Free over 3000', 'rate' => 0, 'free_threshold' => 3000, 'min_order_amount' => 0, 'sort_order' => 2, 'is_active' => true]
        );

        $uae = ShippingZone::firstOrCreate(
            ['market' => 'AE', 'country' => 'UAE'],
            ['name' => 'UAE', 'sort_order' => 1, 'is_active' => true]
        );
        ShippingRule::firstOrCreate(
            ['shipping_zone_id' => $uae->id, 'type' => 'flat'],
            ['name' => 'Standard Delivery', 'rate' => 25, 'sort_order' => 1, 'is_active' => true]
        );
        ShippingRule::firstOrCreate(
            ['shipping_zone_id' => $uae->id, 'type' => 'price_based'],
            ['name' => 'Free over 200 AED', 'rate' => 0, 'free_threshold' => 200, 'min_order_amount' => 0, 'sort_order' => 2, 'is_active' => true]
        );
    }
}
