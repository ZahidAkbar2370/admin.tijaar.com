<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activities')) {
            return;
        }

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_table', 100)->nullable()->index();
            $table->string('action_type', 64)->index();
            $table->string('action_on', 64)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('device', 120)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('location', 255)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
