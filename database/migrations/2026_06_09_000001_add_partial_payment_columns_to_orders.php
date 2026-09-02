<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'online_amount')) {
                $table->decimal('online_amount', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('orders', 'cod_amount')) {
                $table->decimal('cod_amount', 12, 2)->default(0)->after('online_amount');
            }
            if (!Schema::hasColumn('orders', 'partial_payment_percent')) {
                $table->unsignedTinyInteger('partial_payment_percent')->nullable()->after('cod_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['online_amount', 'cod_amount', 'partial_payment_percent'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
