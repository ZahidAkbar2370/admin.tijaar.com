<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('whatsapp_number');
            }
            if (! Schema::hasColumn('users', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('city');
            }
            if (! Schema::hasColumn('users', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('permanent_address');
            }
        });

        if (! Schema::hasTable('phone_verification_otps')) {
            Schema::create('phone_verification_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('phone', 30);
                $table->string('otp_code', 10);
                $table->timestamp('expires_at');
                $table->timestamps();
                $table->index(['user_id', 'otp_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verification_otps');
        Schema::table('users', function (Blueprint $table) {
            foreach (['delivery_address', 'permanent_address', 'city'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
