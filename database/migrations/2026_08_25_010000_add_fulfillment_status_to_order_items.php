<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'fulfillment_status')) {
                $table->string('fulfillment_status', 32)->default('pending')->after('seller_type');
            }
            if (!Schema::hasColumn('order_items', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('fulfillment_status');
            }
            if (!Schema::hasColumn('order_items', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('order_items', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('order_items', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable()->after('rejection_reason');
            }
        });

        // Best-effort backfill from parent order status (single-seller / pre-multi-seller data).
        if (Schema::hasColumn('order_items', 'fulfillment_status')) {
            DB::statement("
                UPDATE order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                SET oi.fulfillment_status = CASE
                    WHEN o.status IN ('cancelled', 'refunded') THEN 'rejected'
                    WHEN o.status IN ('delivered', 'completed') THEN 'delivered'
                    WHEN o.status IN ('shipped') THEN 'shipped'
                    WHEN o.status IN ('approved') THEN 'approved'
                    WHEN o.status IN ('processing', 'paid', 'cancellation_requested') THEN 'processing'
                    ELSE 'pending'
                END
                WHERE oi.fulfillment_status = 'pending' OR oi.fulfillment_status IS NULL OR oi.fulfillment_status = ''
            ");
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            foreach (['refund_amount', 'rejection_reason', 'rejected_at', 'approved_at', 'fulfillment_status'] as $col) {
                if (Schema::hasColumn('order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
