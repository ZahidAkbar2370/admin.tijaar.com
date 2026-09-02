<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FiveSellersSeeder extends Seeder
{
    private function downloadImage(string $url, string $path): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());
                return $path;
            }
        } catch (\Throwable $e) {
            Storage::disk('public')->makeDirectory(dirname($path));
        }
        return null;
    }

    public function run(): void
    {
        $sellersData = [
            [
                'email' => '1@gmail.com',
                'name' => 'Alpha Store',
                'store' => [
                    'name' => 'Alpha Tech & Gadgets',
                    'description' => 'Premium electronics, smartphones, and smart accessories. Your one-stop shop for the latest tech.',
                    'city' => 'Karachi',
                    'country' => 'Pakistan',
                ],
            ],
            [
                'email' => '2@gmail.com',
                'name' => 'Beta Fashion',
                'store' => [
                    'name' => 'Beta Fashion House',
                    'description' => 'Trendy clothing, footwear, and accessories for men and women. Style that speaks.',
                    'city' => 'Lahore',
                    'country' => 'Pakistan',
                ],
            ],
            [
                'email' => '3@gmail.com',
                'name' => 'Gamma Home',
                'store' => [
                    'name' => 'Gamma Home Living',
                    'description' => 'Furniture, décor, and household essentials. Transform your space.',
                    'city' => 'Islamabad',
                    'country' => 'Pakistan',
                ],
            ],
            [
                'email' => '4@gmail.com',
                'name' => 'Delta Sports',
                'store' => [
                    'name' => 'Delta Sports Gear',
                    'description' => 'Fitness equipment, outdoor gear, and activewear. Stay fit, stay strong.',
                    'city' => 'Faisalabad',
                    'country' => 'Pakistan',
                ],
            ],
            [
                'email' => '5@gmail.com',
                'name' => 'Epsilon Beauty',
                'store' => [
                    'name' => 'Epsilon Beauty Hub',
                    'description' => 'Skincare, makeup, and personal care. Beauty made simple.',
                    'city' => 'Rawalpindi',
                    'country' => 'Pakistan',
                ],
            ],
        ];

        $categories = Category::whereNull('parent_id')->orderBy('sort_order')->get();
        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Run TijaarDataSeeder first.');
            return;
        }

        $productsByStore = [
            // Alpha Tech & Gadgets - Electronics
            [
                ['Samsung Galaxy A54 128GB Smartphone', 84999, 94999, '6.4" Super AMOLED, 50MP triple camera, 5000mAh', 'AT-PHONE-001'],
                ['JBL Tune 520BT Wireless Headphones', 4499, 5999, '32hr battery, foldable, Bluetooth 5.3', 'AT-AUDIO-002'],
                ['Anker PowerCore 20000mAh Power Bank', 5999, 7499, 'Dual USB, 18W fast charging, compact', 'AT-PWR-003'],
                ['Logitech MX Master 3S Wireless Mouse', 12499, 14999, 'Ergonomic, 8K DPI, silent clicks', 'AT-PC-004'],
                ['Samsung 32" Smart Monitor M5', 34999, 42999, 'Full HD, Smart TV apps, USB-C', 'AT-MON-005'],
            ],
            // Beta Fashion House - Fashion
            [
                ['Men\'s Slim Fit Oxford Shirt Blue', 2499, 3299, '100% cotton, wrinkle-free, multiple sizes', 'BF-SHIRT-001'],
                ['Women\'s High-Waist Palazzo Pants', 3199, 3999, 'Flowy fabric, elastic waist, elegant', 'BF-PANT-002'],
                ['Genuine Leather Crossbody Bag', 5499, 6999, 'Handcrafted, multiple compartments, gold hardware', 'BF-BAG-003'],
                ['Classic White Sneakers Unisex', 3999, 4999, 'Cushioned sole, breathable, all-day comfort', 'BF-SHOE-004'],
                ['Premium Cashmere Blend Scarf', 2299, 2999, 'Soft, warm, versatile colors', 'BF-SCF-005'],
            ],
            // Gamma Home Living - Home
            [
                ['Adjustable LED Reading Lamp', 2499, 3199, '3 brightness levels, USB port, modern design', 'GH-LAMP-001'],
                ['Memory Foam Orthopedic Pillow', 2999, 3799, 'CertiPUR-US, hypoallergenic, washable', 'GH-PIL-002'],
                ['Borosilicate Glass Coffee Set 6pc', 1299, 1699, 'Heat-resistant, microwave safe, elegant', 'GH-MUG-003'],
                ['5-Tier Wooden Bookshelf', 8999, 11499, 'Solid sheesham wood, easy assembly', 'GH-SLF-004'],
                ['Egyptian Cotton Towel Set 6-Piece', 4499, 5699, '400 GSM, premium absorbency, hotel quality', 'GH-TWL-005'],
            ],
            // Delta Sports Gear - Sports
            [
                ['Adjustable Dumbbells 5kg Pair', 3499, 4499, 'Neoprene coating, non-slip grip, space-saving', 'DS-DUMB-001'],
                ['NBR Yoga Mat 6mm Extra Thick', 1499, 1999, 'Non-slip, eco-friendly, carrying strap included', 'DS-YOGA-002'],
                ['Resistance Bands Set 5 Levels', 999, 1299, 'Door anchor, ankle straps, workout guide', 'DS-RES-003'],
                ['Men\'s Running Shoes Lightweight', 4999, 6499, 'Breathable mesh, cushioned sole, reflective', 'DS-RUN-004'],
                ['Fitness Tracker Smart Band', 2999, 3999, 'Heart rate, sleep, SpO2, 10-day battery', 'DS-BAND-005'],
            ],
            // Epsilon Beauty Hub - Beauty
            [
                ['Vitamin C Brightening Serum 30ml', 2499, 3199, 'Reduces dark spots, antioxidant, fragrance-free', 'EB-SRM-001'],
                ['Hyaluronic Acid Face Moisturizer', 1899, 2499, 'SPF 30, 48hr hydration, lightweight', 'EB-MOIS-002'],
                ['Natural Bristle Hair Brush', 799, 999, 'Boar bristle, detangling, reduces frizz', 'EB-BRSH-003'],
                ['Eau de Parfum Floral 50ml', 4499, 5699, 'Long-lasting, luxury fragrance, gift box', 'EB-PRF-004'],
                ['Charcoal Clay Face Mask 100ml', 1299, 1699, 'Deep cleansing, purifying, suitable for oily skin', 'EB-MASK-005'],
            ],
        ];

        $imgSeeds = ['phone', 'headphones', 'charger', 'mouse', 'monitor', 'shirt', 'pants', 'bag', 'shoes', 'scarf', 'lamp', 'pillow', 'mug', 'shelf', 'towel', 'dumbbell', 'yoga', 'bands', 'runningshoe', 'fitnessband', 'serum', 'moisturizer', 'brush', 'perfume', 'mask'];

        foreach ($sellersData as $idx => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('khankhan'),
                    'role' => 'seller',
                    'phone' => '+92 3' . rand(10, 99) . rand(1000000, 9999999),
                    'email_verified_at' => now(),
                ]
            );

            $seller = Seller::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'approved', 'kyc_status' => 'verified', 'approved_at' => now()]
            );

            $storeSlug = Str::slug($data['store']['name']);
            $logoPath = $this->downloadImage("https://picsum.photos/seed/store{$idx}/200/200", "stores/{$storeSlug}-logo.jpg");

            $store = Store::firstOrCreate(
                ['seller_id' => $seller->id],
                [
                    'name' => $data['store']['name'],
                    'slug' => $storeSlug,
                    'description' => $data['store']['description'],
                    'logo' => $logoPath,
                    'country' => $data['store']['country'],
                    'city' => $data['store']['city'],
                    'address' => rand(1, 99) . ' Main Street',
                    'phone' => '+92 3' . rand(10, 99) . rand(1000000, 9999999),
                    'email' => $user->email,
                    'is_active' => true,
                ]
            );

            // Remove old products so we replace with realistic ones
            Product::where('store_id', $store->id)->delete();

            // 5 realistic products per seller
            $storeProducts = $productsByStore[$idx] ?? $productsByStore[0];
            foreach ($storeProducts as $i => $template) {
                [$name, $price, $comparePrice, $shortDesc, $sku] = $template;
                $category = $categories[$idx % $categories->count()];
                $slug = Str::slug($name) . '-s' . $idx . '-' . $i . '-' . $store->id;

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'store_id' => $store->id,
                        'seller_id' => $user->id,
                        'seller_type' => 'business',
                        'category_id' => $category->id,
                        'name' => $name,
                        'short_description' => $shortDesc,
                        'description' => "{$shortDesc}. Premium quality from {$store->name}. Authentic product with warranty. Fast delivery across Pakistan. Contact us for bulk orders.",
                        'status' => 'published',
                        'condition' => 'new',
                        'price' => $price,
                        'compare_at_price' => $comparePrice,
                        'quantity' => rand(20, 100),
                        'track_inventory' => true,
                        'is_featured' => $i < 2,
                        'is_hot' => $i === 0,
                        'sku' => $sku,
                    ]
                );

                $imgSeed = $imgSeeds[($idx * 5 + $i) % count($imgSeeds)];
                $imgPath = "products/seller-{$idx}-{$i}.jpg";
                $savedPath = $this->downloadImage("https://picsum.photos/seed/{$imgSeed}/600/600", $imgPath);

                if ($savedPath) {
                    ProductMedia::updateOrCreate(
                        ['product_id' => $product->id, 'sort_order' => 0],
                        ['type' => 'image', 'path' => $savedPath, 'alt_text' => $name, 'is_thumbnail' => true]
                    );
                }
            }
        }

        $this->command->info('Created 5 sellers (1@gmail.com - 5@gmail.com), password: khankhan. Each has a store with 5 products.');
    }
}
