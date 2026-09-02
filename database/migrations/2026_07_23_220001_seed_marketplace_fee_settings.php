<?php

use App\Models\Setting;
use App\Services\MarketplaceFeeService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['key' => 'marketplace_fee_type', 'value' => 'fixed', 'group' => 'fees'],
            ['key' => 'marketplace_fee_value', 'value' => '0', 'group' => 'fees'],
            ['key' => 'online_transaction_fee_type', 'value' => 'fixed', 'group' => 'fees'],
            ['key' => 'online_transaction_fee_value', 'value' => '0', 'group' => 'fees'],
            ['key' => 'seller_commission_type', 'value' => 'percentage', 'group' => 'fees'],
            ['key' => 'seller_commission_value', 'value' => '2', 'group' => 'fees'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }

        try {
            MarketplaceFeeService::syncGlobalSellerCommissionRule();
        } catch (\Throwable $e) {
            // commissions table may not exist in some envs; ignore
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'marketplace_fee_type',
            'marketplace_fee_value',
            'online_transaction_fee_type',
            'online_transaction_fee_value',
            'seller_commission_type',
            'seller_commission_value',
        ])->delete();
    }
};
