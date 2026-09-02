<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('customer_notes');
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('orders', 'cancellation_requested_at')) {
                $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('orders', 'seller_approved_at')) {
                $table->timestamp('seller_approved_at')->nullable()->after('cancellation_requested_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'payout_hold_days')) {
                $table->unsignedInteger('payout_hold_days')->nullable()->after('private_listing_limit');
            }
            if (!Schema::hasColumn('users', 'private_seller_kyc_status')) {
                $table->string('private_seller_kyc_status')->nullable()->after('payout_hold_days');
            }
        });

        if (Setting::get('private_listing_fee') === null) {
            Setting::set('private_listing_fee', '50');
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['rejection_reason', 'cancellation_reason', 'cancellation_requested_at', 'seller_approved_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['payout_hold_days', 'private_seller_kyc_status'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
