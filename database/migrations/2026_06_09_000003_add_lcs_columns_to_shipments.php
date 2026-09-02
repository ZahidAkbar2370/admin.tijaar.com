<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'shipping_cost')) {
                $table->decimal('shipping_cost', 12, 2)->default(0)->after('seller_id');
            }
            if (!Schema::hasColumn('shipments', 'lcs_booking_id')) {
                $table->string('lcs_booking_id')->nullable()->after('tracking_url');
            }
            if (!Schema::hasColumn('shipments', 'lcs_cn_number')) {
                $table->string('lcs_cn_number')->nullable()->after('lcs_booking_id');
            }
            if (!Schema::hasColumn('shipments', 'lcs_raw_response')) {
                $table->json('lcs_raw_response')->nullable()->after('lcs_cn_number');
            }
            if (!Schema::hasColumn('shipments', 'weight_kg')) {
                $table->decimal('weight_kg', 8, 3)->nullable()->after('lcs_raw_response');
            }
            if (!Schema::hasColumn('shipments', 'pieces')) {
                $table->unsignedSmallInteger('pieces')->default(1)->after('weight_kg');
            }
            if (!Schema::hasColumn('shipments', 'pickup_type')) {
                $table->string('pickup_type', 32)->default('seller_dropoff')->after('pieces');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            foreach (['shipping_cost', 'lcs_booking_id', 'lcs_cn_number', 'lcs_raw_response', 'weight_kg', 'pieces', 'pickup_type'] as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
