<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sitemap_static_urls')) {
            return;
        }

        Schema::create('sitemap_static_urls', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('changefreq', 20)->default('monthly');
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['path' => '/', 'changefreq' => 'daily', 'priority' => 1.0, 'sort_order' => 1],
            ['path' => '/shop', 'changefreq' => 'daily', 'priority' => 0.9, 'sort_order' => 2],
            ['path' => '/blogs', 'changefreq' => 'weekly', 'priority' => 0.6, 'sort_order' => 3],
            ['path' => '/about', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 4],
            ['path' => '/contact', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 5],
            ['path' => '/faqs', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 6],
            ['path' => '/help', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 7],
            ['path' => '/returns-refunds', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 8],
            ['path' => '/shipping', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 9],
            ['path' => '/how-it-works', 'changefreq' => 'monthly', 'priority' => 0.5, 'sort_order' => 10],
            ['path' => '/terms', 'changefreq' => 'monthly', 'priority' => 0.4, 'sort_order' => 11],
            ['path' => '/privacy', 'changefreq' => 'monthly', 'priority' => 0.4, 'sort_order' => 12],
            ['path' => '/cookie-policy', 'changefreq' => 'monthly', 'priority' => 0.4, 'sort_order' => 13],
            ['path' => '/sellers', 'changefreq' => 'weekly', 'priority' => 0.6, 'sort_order' => 14],
            ['path' => '/all-categories', 'changefreq' => 'weekly', 'priority' => 0.7, 'sort_order' => 15],
        ];

        foreach ($defaults as $row) {
            DB::table('sitemap_static_urls')->insert(array_merge($row, [
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_static_urls');
    }
};
