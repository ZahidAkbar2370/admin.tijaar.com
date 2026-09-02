<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sitemap_overrides')) {
            return;
        }

        Schema::create('sitemap_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('file_key')->unique();
            $table->string('mode', 10)->default('auto');
            $table->longText('manual_xml')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_overrides');
    }
};
