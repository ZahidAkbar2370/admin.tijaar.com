<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('balance', 12, 2)->default(0);
                $table->string('currency', 8)->default('PKR');
                $table->timestamps();
                $table->unique('user_id');
            });
        }

        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // credit, debit, refund, payout, order_payment
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2)->nullable();
                $table->string('reference_type')->nullable(); // order, refund, payout
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['wallet_id', 'created_at']);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
