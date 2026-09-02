<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([MarketSeeder::class, RoleSeeder::class, SellerStoreSeeder::class, ProductSeeder::class, ProductStockSeeder::class, SettingSeeder::class, CommissionSeeder::class, EmailTemplateSeeder::class, WhatsappTemplateSeeder::class]);

        User::firstOrCreate(
            ['email' => 'admin@tijaar.com'],
            [
                'name' => 'Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer@tijaar.com'],
            [
                'name' => 'Test Customer',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'customer',
            ]
        );
    }
}
