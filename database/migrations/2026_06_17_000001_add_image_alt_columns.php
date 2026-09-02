<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (! Schema::hasColumn('categories', 'image_alt')) {
                    $table->string('image_alt', 255)->nullable();
                }
                if (! Schema::hasColumn('categories', 'banner_image_alt')) {
                    $table->string('banner_image_alt', 255)->nullable();
                }
            });
        }

        if (Schema::hasTable('brands') && ! Schema::hasColumn('brands', 'logo_alt')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('logo_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('blogs') && ! Schema::hasColumn('blogs', 'featured_image_alt')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->string('featured_image_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('testimonials') && ! Schema::hasColumn('testimonials', 'avatar_alt')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->string('avatar_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('banners') && ! Schema::hasColumn('banners', 'image_alt')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'thumbnail_alt')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('thumbnail_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('product_variants') && ! Schema::hasColumn('product_variants', 'image_alt')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (! Schema::hasColumn('stores', 'logo_alt')) {
                    $table->string('logo_alt', 255)->nullable();
                }
                if (! Schema::hasColumn('stores', 'banner_alt')) {
                    $table->string('banner_alt', 255)->nullable();
                }
                if (! Schema::hasColumn('stores', 'cover_image_alt')) {
                    $table->string('cover_image_alt', 255)->nullable();
                }
            });
        }

        if (Schema::hasTable('flash_deals') && ! Schema::hasColumn('flash_deals', 'image_alt')) {
            Schema::table('flash_deals', function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'avatar_alt')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar_alt', 255)->nullable();
            });
        }

        if (Schema::hasTable('review_media') && ! Schema::hasColumn('review_media', 'alt_text')) {
            Schema::table('review_media', function (Blueprint $table) {
                $table->string('alt_text', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        $drop = function (string $tableName, array $columns) {
            if (! Schema::hasTable($tableName)) {
                return;
            }
            Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn($tableName, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        };

        $drop('categories', ['image_alt', 'banner_image_alt']);
        $drop('brands', ['logo_alt']);
        $drop('blogs', ['featured_image_alt']);
        $drop('testimonials', ['avatar_alt']);
        $drop('banners', ['image_alt']);
        $drop('products', ['thumbnail_alt']);
        $drop('product_variants', ['image_alt']);
        $drop('stores', ['logo_alt', 'banner_alt', 'cover_image_alt']);
        $drop('flash_deals', ['image_alt']);
        $drop('users', ['avatar_alt']);
        $drop('review_media', ['alt_text']);
    }
};
