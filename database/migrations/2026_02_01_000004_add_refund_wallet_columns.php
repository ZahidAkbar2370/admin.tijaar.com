<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refunds') && !Schema::hasColumn('refunds', 'refund_type')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->string('refund_type')->default('gateway')->after('status');
                $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('gateway_refund_id');
            });
            Schema::table('refunds', function (Blueprint $table) {
                $table->foreign('wallet_transaction_id')->references('id')->on('wallet_transactions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refunds') && Schema::hasColumn('refunds', 'refund_type')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropForeign(['wallet_transaction_id']);
                $table->dropColumn(['refund_type', 'wallet_transaction_id']);
            });
        }
    }
};
