<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('marketplace_fee_allocated', 12, 2)->default(0)->after('commission_amount');
            $table->decimal('online_transaction_fee_allocated', 12, 2)->default(0)->after('marketplace_fee_allocated');
        });

        $this->copySettingIfMissing('buyer_marketplace_fee_type', 'marketplace_fee_type');
        $this->copySettingIfMissing('buyer_marketplace_fee_value', 'marketplace_fee_value');
        $this->copySettingIfMissing('buyer_online_transaction_fee_type', 'online_transaction_fee_type');
        $this->copySettingIfMissing('buyer_online_transaction_fee_value', 'online_transaction_fee_value');
        $this->copySettingIfMissing('private_seller_marketplace_fee_type', 'marketplace_fee_type');
        $this->copySettingIfMissing('private_seller_marketplace_fee_value', 'marketplace_fee_value');
        $this->copySettingIfMissing('private_seller_online_transaction_fee_type', 'online_transaction_fee_type');
        $this->copySettingIfMissing('private_seller_online_transaction_fee_value', 'online_transaction_fee_value');
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['marketplace_fee_allocated', 'online_transaction_fee_allocated']);
        });
    }

    private function copySettingIfMissing(string $newKey, string $legacyKey): void
    {
        if (DB::table('settings')->where('key', $newKey)->exists()) {
            return;
        }
        $legacy = DB::table('settings')->where('key', $legacyKey)->value('value');
        if ($legacy === null) {
            return;
        }
        DB::table('settings')->insert([
            'key' => $newKey,
            'value' => $legacy,
            'group' => 'fees',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
