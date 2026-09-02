<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('products', 'is_hot')) {
                $table->boolean('is_hot')->default(false)->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'video_url')) {
                $table->string('video_url')->nullable()->after('thumbnail_path');
            }
        });

        if (!Schema::hasTable('product_documents')) {
            Schema::create('product_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('type')->default('manual'); // manual, spec_sheet, warranty, other
                $table->string('label')->nullable();
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('product_media', function (Blueprint $table) {
            if (!Schema::hasColumn('product_media', 'is_thumbnail')) {
                $table->boolean('is_thumbnail')->default(false)->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'is_hot', 'video_url']);
        });
        Schema::dropIfExists('product_documents');
        Schema::table('product_media', function (Blueprint $table) {
            $table->dropColumn('is_thumbnail');
        });
    }
};
