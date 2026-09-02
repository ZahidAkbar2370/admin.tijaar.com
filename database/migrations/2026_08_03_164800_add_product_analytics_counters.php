<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('impressions_count')->default(0)->after('is_new_arrival');
            $table->unsignedBigInteger('clicks_count')->default(0)->after('impressions_count');
            $table->unsignedBigInteger('shares_count')->default(0)->after('clicks_count');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['impressions_count', 'clicks_count', 'shares_count']);
        });
    }
};
