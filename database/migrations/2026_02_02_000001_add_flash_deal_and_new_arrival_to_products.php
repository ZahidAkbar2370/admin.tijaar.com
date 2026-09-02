<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'flash_deal_discount_type')) {
                $table->string('flash_deal_discount_type', 20)->nullable()->after('is_hot');
            }
            if (!Schema::hasColumn('products', 'flash_deal_discount_value')) {
                $table->decimal('flash_deal_discount_value', 12, 2)->nullable()->after('flash_deal_discount_type');
            }
            if (!Schema::hasColumn('products', 'flash_deal_ends_at')) {
                $table->timestamp('flash_deal_ends_at')->nullable()->after('flash_deal_discount_value');
            }
            if (!Schema::hasColumn('products', 'is_new_arrival')) {
                $table->boolean('is_new_arrival')->default(false)->after('flash_deal_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'flash_deal_discount_type',
                'flash_deal_discount_value',
                'flash_deal_ends_at',
                'is_new_arrival',
            ]);
        });
    }
};
