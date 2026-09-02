<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('market', 4)->default('PK'); // PK, AE
            $table->string('country')->nullable(); // Pakistan, UAE - null = all
            $table->json('regions')->nullable(); // specific cities/regions if needed
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('type'); // flat, weight_based, price_based
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('min_order_amount', 12, 2)->nullable(); // for price_based
            $table->decimal('free_threshold', 12, 2)->nullable(); // free shipping above this
            $table->decimal('min_weight_kg', 10, 2)->nullable();
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
        Schema::dropIfExists('shipping_zones');
    }
};
