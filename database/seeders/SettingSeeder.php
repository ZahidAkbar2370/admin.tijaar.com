<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'private_sellers_enabled', 'value' => '1', 'group' => 'private_seller'],
            ['key' => 'private_listing_free_limit', 'value' => '3', 'group' => 'private_seller'],
            ['key' => 'private_listing_limit', 'value' => '15', 'group' => 'private_seller'],
            ['key' => 'private_listing_approval', 'value' => '0', 'group' => 'private_seller'],
            ['key' => 'private_listing_expiry_days', 'value' => '30', 'group' => 'private_seller'],
            ['key' => 'marketplace_fee_type', 'value' => 'fixed', 'group' => 'fees'],
            ['key' => 'marketplace_fee_value', 'value' => '0', 'group' => 'fees'],
            ['key' => 'online_transaction_fee_type', 'value' => 'fixed', 'group' => 'fees'],
            ['key' => 'online_transaction_fee_value', 'value' => '0', 'group' => 'fees'],
            ['key' => 'seller_commission_type', 'value' => 'percentage', 'group' => 'fees'],
            ['key' => 'seller_commission_value', 'value' => '2', 'group' => 'fees'],
        ];

        foreach ($defaults as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
