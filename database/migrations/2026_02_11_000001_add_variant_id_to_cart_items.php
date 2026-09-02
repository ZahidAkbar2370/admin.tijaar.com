<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $name): bool
    {
        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]);
        return !empty($result);
    }

    public function up(): void
    {
        if (!Schema::hasColumn('cart_items', 'variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('variant_id')->default(0)->after('product_id');
            });
        }

        // Add new unique index first so FKs still have a supporting index
        if (!$this->indexExists('cart_items', 'cart_items_cart_product_variant_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id', 'variant_id'], 'cart_items_cart_product_variant_unique');
            });
        }

        if ($this->indexExists('cart_items', 'cart_items_cart_product_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_product_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('cart_items', 'cart_items_cart_product_variant_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_cart_product_variant_unique');
            });
        }
        if (Schema::hasColumn('cart_items', 'variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('variant_id');
            });
        }
        if (!$this->indexExists('cart_items', 'cart_items_cart_product_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id'], 'cart_items_cart_product_unique');
            });
        }
    }
};
