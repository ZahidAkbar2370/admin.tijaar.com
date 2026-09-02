<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'whatsapp_verified_at')) {
                    $table->timestamp('whatsapp_verified_at')->nullable()->after('phone_verified_at');
                }
            });
        }

        if (! Schema::hasTable('whatsapp_verification_otps')) {
            Schema::create('whatsapp_verification_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('phone', 20);
                $table->string('otp_code', 6);
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['user_id', 'phone']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_verification_otps');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'whatsapp_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('whatsapp_verified_at');
            });
        }
    }
};
