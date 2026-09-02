<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('seller_type')->default('business')->after('id');
            $table->foreignId('seller_id')->nullable()->after('seller_type')->constrained('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });
        DB::statement('ALTER TABLE products MODIFY store_id BIGINT UNSIGNED NULL');
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });
        DB::statement('ALTER TABLE products MODIFY store_id BIGINT UNSIGNED NOT NULL');
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
            $table->dropColumn('seller_type');
        });
    }
};
