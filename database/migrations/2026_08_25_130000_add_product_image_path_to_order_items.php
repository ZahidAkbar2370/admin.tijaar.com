<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_image_path')) {
                $table->string('product_image_path')->nullable()->after('product_sku');
            }
        });

        // Backfill from live products (including soft-deleted) so existing orders keep images.
        if (Schema::hasColumn('order_items', 'product_image_path')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("
                    UPDATE order_items oi
                    INNER JOIN products p ON p.id = oi.product_id
                    SET oi.product_image_path = COALESCE(
                        NULLIF(p.thumbnail_path, ''),
                        (
                            SELECT pm.path
                            FROM product_media pm
                            WHERE pm.product_id = p.id
                            ORDER BY pm.is_thumbnail DESC, pm.sort_order ASC, pm.id ASC
                            LIMIT 1
                        )
                    )
                    WHERE oi.product_image_path IS NULL
                      AND oi.product_id IS NOT NULL
                ");
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_image_path')) {
                $table->dropColumn('product_image_path');
            }
        });
    }
};
