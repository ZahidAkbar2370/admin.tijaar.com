<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('flagged_items')) {
            Schema::create('flagged_items', function (Blueprint $table) {
                $table->id();
                $table->string('flaggable_type'); // product, store, listing
                $table->unsignedBigInteger('flaggable_id');
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending'); // pending, reviewed, resolved, dismissed
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['flaggable_type', 'flaggable_id']);
            });
        }

        if (!Schema::hasTable('abuse_safety_settings')) {
            Schema::create('abuse_safety_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flagged_items');
        Schema::dropIfExists('abuse_safety_settings');
    }
};
