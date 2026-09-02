<?php

namespace Database\Seeders;

use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SellerStoreSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = [
            [
                'name' => 'Ahmed Khan',
                'email' => 'ahmed@techmart.pk',
                'store' => ['name' => 'TechMart Pakistan', 'description' => 'Electronics & gadgets store', 'country' => 'Pakistan', 'city' => 'Karachi'],
            ],
            [
                'name' => 'Sara Ali',
                'email' => 'sara@fashionhub.ae',
                'store' => ['name' => 'Fashion Hub UAE', 'description' => 'Trendy fashion & accessories', 'country' => 'UAE', 'city' => 'Dubai'],
            ],
            [
                'name' => 'Hassan Raza',
                'email' => 'hassan@homeworld.pk',
                'store' => ['name' => 'HomeWorld', 'description' => 'Home décor & furniture', 'country' => 'Pakistan', 'city' => 'Lahore'],
            ],
            [
                'name' => 'Fatima Noor',
                'email' => 'fatima@beautystore.ae',
                'store' => ['name' => 'Beauty Store UAE', 'description' => 'Cosmetics & skincare', 'country' => 'UAE', 'city' => 'Abu Dhabi'],
            ],
            [
                'name' => 'Omar Sheikh',
                'email' => 'omar@bookhaven.pk',
                'store' => ['name' => 'Book Haven', 'description' => 'Books & stationery', 'country' => 'Pakistan', 'city' => 'Islamabad'],
            ],
        ];

        foreach ($sellers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'seller',
                    'phone' => '+92' . rand(3000000000, 3499999999),
                ]
            );

            $seller = Seller::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'approved',
                    'kyc_status' => 'verified',
                    'approved_at' => now(),
                ]
            );

            $storeData = $data['store'];
            Store::firstOrCreate(
                ['seller_id' => $seller->id],
                [
                    'name' => $storeData['name'],
                    'slug' => \Illuminate\Support\Str::slug($storeData['name']),
                    'description' => $storeData['description'],
                    'country' => $storeData['country'],
                    'city' => $storeData['city'],
                    'address' => '123 Main Street',
                    'phone' => '+92' . rand(3000000000, 3499999999),
                    'email' => $user->email,
                    'is_active' => true,
                ]
            );
        }
    }
}
