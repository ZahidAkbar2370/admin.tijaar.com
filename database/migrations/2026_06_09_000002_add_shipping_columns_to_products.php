<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'weight_kg')) {
                $table->decimal('weight_kg', 8, 3)->default(0.5)->after('quantity');
            }
            if (!Schema::hasColumn('products', 'length_cm')) {
                $table->unsignedSmallInteger('length_cm')->nullable()->after('weight_kg');
            }
            if (!Schema::hasColumn('products', 'width_cm')) {
                $table->unsignedSmallInteger('width_cm')->nullable()->after('length_cm');
            }
            if (!Schema::hasColumn('products', 'height_cm')) {
                $table->unsignedSmallInteger('height_cm')->nullable()->after('width_cm');
            }
            if (!Schema::hasColumn('products', 'shipping_mode')) {
                $table->string('shipping_mode', 32)->default('customer_pays')->after('height_cm');
            }
            if (!Schema::hasColumn('products', 'shipping_cost_cached')) {
                $table->decimal('shipping_cost_cached', 12, 2)->nullable()->after('shipping_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['weight_kg', 'length_cm', 'width_cm', 'height_cm', 'shipping_mode', 'shipping_cost_cached'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
