<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('location_provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('location_countries')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['country_id', 'name']);
        });

        Schema::create('location_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('location_provinces')->cascadeOnDelete();
            $table->string('name');
            $table->string('leopards_city_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['province_id', 'name']);
            $table->index('leopards_city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_cities');
        Schema::dropIfExists('location_provinces');
        Schema::dropIfExists('location_countries');
    }
};
