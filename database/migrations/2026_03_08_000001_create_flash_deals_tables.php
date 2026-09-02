<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('image_path')->nullable();
            $table->string('discount_type', 20)->default('percentage'); // percentage, fixed
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('flash_deal_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_deal_id')->constrained('flash_deals')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['flash_deal_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_deal_product');
        Schema::dropIfExists('flash_deals');
    }
};
