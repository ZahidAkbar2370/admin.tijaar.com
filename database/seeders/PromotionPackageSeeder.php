<?php

namespace Database\Seeders;

use App\Models\PromotionPackage;
use Illuminate\Database\Seeder;

class PromotionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Featured Product', 'type' => 'featured_product', 'price' => 500, 'duration_days' => 7],
            ['name' => 'Hot Sale', 'type' => 'hot_sale', 'price' => 300, 'duration_days' => 3],
            ['name' => 'Featured Shop', 'type' => 'featured_shop', 'price' => 2000, 'duration_days' => 14],
            ['name' => 'Store Banner', 'type' => 'store_banner', 'price' => 1000, 'duration_days' => 7],
        ];

        foreach ($packages as $i => $p) {
            PromotionPackage::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($p['name'])],
                array_merge($p, [
                    'description' => 'Promote your ' . str_replace('_', ' ', $p['type']),
                    'seller_type_eligibility' => 'both',
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ])
            );
        }
    }
}
