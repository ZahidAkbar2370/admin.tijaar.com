<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'private_listing_free_limit'],
            ['value' => '3', 'group' => 'private_seller']
        );

        $max = Setting::where('key', 'private_listing_limit')->first();
        if (!$max) {
            Setting::create(['key' => 'private_listing_limit', 'value' => '15', 'group' => 'private_seller']);
        }
    }

    public function down(): void
    {
        Setting::where('key', 'private_listing_free_limit')->delete();
    }
};
