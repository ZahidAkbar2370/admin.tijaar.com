<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('sku')->nullable()->unique();
                $table->string('name')->nullable(); // e.g. "Red / Large"
                $table->json('attributes')->nullable(); // {"color":"Red","size":"L"}
                $table->decimal('price', 12, 2);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->string('image_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name'); // e.g. color, size
                $table->string('value'); // e.g. Red, Large
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_tag')) {
            Schema::create('product_tag', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->primary(['product_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['product_id', 'category_id']);
            });
        }

        if (!Schema::hasTable('reserved_stock')) {
            Schema::create('reserved_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->string('reference_type'); // cart, order_hold
                $table->string('reference_id'); // cart_id or session_id
                $table->timestamp('expires_at');
                $table->timestamps();
                $table->index(['product_id', 'reference_type', 'reference_id']);
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_stock');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_variants');
    }
};
