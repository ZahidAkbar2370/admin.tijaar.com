<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('role', 32)->default('customer')->after('remember_token'); // admin, sub_admin, seller, customer
            $table->boolean('is_private_seller')->default(false)->after('role');
            $table->unsignedTinyInteger('private_listing_limit')->default(10)->after('is_private_seller');
            $table->boolean('is_suspended')->default(false)->after('private_listing_limit');
            $table->boolean('is_banned')->default(false)->after('is_suspended');
            $table->timestamp('last_login_at')->nullable()->after('is_banned');
            $table->boolean('two_factor_enabled')->default(false)->after('last_login_at');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->unsignedInteger('abuse_score')->default(0)->after('two_factor_secret');
            $table->json('preferences')->nullable()->after('abuse_score');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'phone_verified_at', 'role',
                'is_private_seller', 'private_listing_limit', 'is_suspended',
                'is_banned', 'last_login_at', 'two_factor_enabled', 'two_factor_secret',
                'abuse_score', 'preferences', 'deleted_at'
            ]);
        });
    }
};
