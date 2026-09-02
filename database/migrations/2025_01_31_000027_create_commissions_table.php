<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type'); // global, category, seller_type, seller
            $table->unsignedBigInteger('scope_id')->nullable(); // category_id or seller user_id
            $table->string('seller_type')->nullable(); // business, private
            $table->string('commission_type'); // percentage, fixed
            $table->decimal('value', 10, 2);
            $table->unsignedInteger('priority')->default(0); // higher = more specific
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
