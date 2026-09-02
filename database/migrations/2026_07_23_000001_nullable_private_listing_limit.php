<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // null = use global Setting::private_listing_limit
        if (Schema::hasColumn('users', 'private_listing_limit')) {
            DB::statement('ALTER TABLE users MODIFY private_listing_limit TINYINT UNSIGNED NULL DEFAULT NULL');
            DB::table('users')->update(['private_listing_limit' => null]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'private_listing_limit')) {
            DB::table('users')->whereNull('private_listing_limit')->update(['private_listing_limit' => 10]);
            DB::statement('ALTER TABLE users MODIFY private_listing_limit TINYINT UNSIGNED NOT NULL DEFAULT 10');
        }
    }
};
