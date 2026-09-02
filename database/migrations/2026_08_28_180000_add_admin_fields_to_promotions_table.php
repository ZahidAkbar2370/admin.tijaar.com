<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'payment_status')) {
                $table->string('payment_status', 32)->default('paid')->after('payment_ref');
            }
            if (!Schema::hasColumn('promotions', 'assigned_by_user_id')) {
                $table->foreignId('assigned_by_user_id')->nullable()->after('payment_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('promotions', 'paid_by')) {
                $table->string('paid_by', 32)->nullable()->after('assigned_by_user_id');
            }
            if (!Schema::hasColumn('promotions', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('promotions', 'payment_link_token')) {
                $table->string('payment_link_token', 64)->nullable()->unique()->after('admin_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $cols = ['payment_link_token', 'admin_note', 'paid_by', 'assigned_by_user_id', 'payment_status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('promotions', $col)) {
                    if ($col === 'assigned_by_user_id') {
                        $table->dropForeign(['assigned_by_user_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
