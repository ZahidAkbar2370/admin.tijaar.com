<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'marketplace_fee')) {
                $table->decimal('marketplace_fee', 12, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'online_transaction_fee')) {
                $table->decimal('online_transaction_fee', 12, 2)->default(0)->after('marketplace_fee');
            }
            if (!Schema::hasColumn('orders', 'seller_commission_total')) {
                $table->decimal('seller_commission_total', 12, 2)->default(0)->after('online_transaction_fee');
            }
            if (!Schema::hasColumn('orders', 'platform_revenue')) {
                $table->decimal('platform_revenue', 12, 2)->default(0)->after('seller_commission_total');
            }
            if (!Schema::hasColumn('orders', 'marketplace_fee_type')) {
                $table->string('marketplace_fee_type', 20)->nullable()->after('platform_revenue');
            }
            if (!Schema::hasColumn('orders', 'marketplace_fee_rate')) {
                $table->decimal('marketplace_fee_rate', 12, 2)->nullable()->after('marketplace_fee_type');
            }
            if (!Schema::hasColumn('orders', 'online_transaction_fee_type')) {
                $table->string('online_transaction_fee_type', 20)->nullable()->after('marketplace_fee_rate');
            }
            if (!Schema::hasColumn('orders', 'online_transaction_fee_rate')) {
                $table->decimal('online_transaction_fee_rate', 12, 2)->nullable()->after('online_transaction_fee_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'marketplace_fee',
                'online_transaction_fee',
                'seller_commission_total',
                'platform_revenue',
                'marketplace_fee_type',
                'marketplace_fee_rate',
                'online_transaction_fee_type',
                'online_transaction_fee_rate',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
