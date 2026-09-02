<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'seller_marketplace_fee_total')) {
                $table->decimal('seller_marketplace_fee_total', 12, 2)->default(0)->after('seller_commission_total');
            }
            if (! Schema::hasColumn('orders', 'seller_online_transaction_fee_total')) {
                $table->decimal('seller_online_transaction_fee_total', 12, 2)->default(0)->after('seller_marketplace_fee_total');
            }
            if (! Schema::hasColumn('orders', 'seller_marketplace_fee_type')) {
                $table->string('seller_marketplace_fee_type', 20)->nullable()->after('seller_online_transaction_fee_total');
            }
            if (! Schema::hasColumn('orders', 'seller_marketplace_fee_rate')) {
                $table->decimal('seller_marketplace_fee_rate', 12, 2)->nullable()->after('seller_marketplace_fee_type');
            }
            if (! Schema::hasColumn('orders', 'seller_online_transaction_fee_type')) {
                $table->string('seller_online_transaction_fee_type', 20)->nullable()->after('seller_marketplace_fee_rate');
            }
            if (! Schema::hasColumn('orders', 'seller_online_transaction_fee_rate')) {
                $table->decimal('seller_online_transaction_fee_rate', 12, 2)->nullable()->after('seller_online_transaction_fee_type');
            }
            if (! Schema::hasColumn('orders', 'seller_commission_type')) {
                $table->string('seller_commission_type', 20)->nullable()->after('seller_online_transaction_fee_rate');
            }
            if (! Schema::hasColumn('orders', 'seller_commission_rate')) {
                $table->decimal('seller_commission_rate', 12, 2)->nullable()->after('seller_commission_type');
            }
        });

        if (Schema::hasTable('order_items')) {
            DB::table('orders')->orderBy('id')->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $totals = DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->selectRaw('COALESCE(SUM(marketplace_fee_allocated), 0) as mp, COALESCE(SUM(online_transaction_fee_allocated), 0) as ot')
                        ->first();
                    if (! $totals) {
                        continue;
                    }
                    DB::table('orders')->where('id', $order->id)->update([
                        'seller_marketplace_fee_total' => round((float) $totals->mp, 2),
                        'seller_online_transaction_fee_total' => round((float) $totals->ot, 2),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'seller_marketplace_fee_total',
                'seller_online_transaction_fee_total',
                'seller_marketplace_fee_type',
                'seller_marketplace_fee_rate',
                'seller_online_transaction_fee_type',
                'seller_online_transaction_fee_rate',
                'seller_commission_type',
                'seller_commission_rate',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
