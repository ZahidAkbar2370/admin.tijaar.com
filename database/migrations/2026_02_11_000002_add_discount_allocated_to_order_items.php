<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('order_items', 'discount_allocated')) {
            return;
        }
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount_allocated', 12, 2)->default(0)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount_allocated');
        });
    }
};
