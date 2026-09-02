<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tijaar_expenses')) {
            return;
        }

        Schema::create('tijaar_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 100);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date')->nullable();
            $table->string('proof_image')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tijaar_expenses');
    }
};
