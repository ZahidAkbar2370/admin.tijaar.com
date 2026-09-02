<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'tcs_booking_id')) {
                $table->string('tcs_booking_id')->nullable()->after('lcs_raw_response');
            }
            if (!Schema::hasColumn('shipments', 'tcs_cn_number')) {
                $table->string('tcs_cn_number')->nullable()->after('tcs_booking_id');
            }
            if (!Schema::hasColumn('shipments', 'tcs_raw_response')) {
                $table->json('tcs_raw_response')->nullable()->after('tcs_cn_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            foreach (['tcs_booking_id', 'tcs_cn_number', 'tcs_raw_response'] as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
