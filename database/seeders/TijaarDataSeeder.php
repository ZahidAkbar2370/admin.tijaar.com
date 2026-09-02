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

class TijaarDataSeeder extends Seeder
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
            // Fallback: create placeholder directory
            Storage::disk('public')->makeDirectory(dirname($path));
        }
        return null;
    }

    public function run(): void
    {
        // 1. Ensure samsaftech@gmail.com seller & store exist
        $user = User::firstOrCreate(
            ['email' => 'samsaftech@gmail.com'],
            [
                'name' => 'Samsaf Tech',
                'password' => Hash::make('password'),
                'role' => 'seller',
                'phone' => '+92 300 1234567',
                'email_verified_at' => now(),
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

        $store = Store::firstOrCreate(
            ['seller_id' => $seller->id],
            [
                'name' => 'Samsaf Tech Store',
                'slug' => 'samsaf-tech-store',
                'description' => 'Your trusted source for electronics, fashion, home goods, and more. Premium quality at unbeatable prices.',
                'country' => 'Pakistan',
                'city' => 'Karachi',
                'address' => 'Block 5, Clifton, Karachi',
                'phone' => '+92 300 1234567',
                'email' => $user->email,
                'is_active' => true,
            ]
        );

        // 2. Define 10 categories with 5 subcategories each
        $categoriesData = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'icon' => 'smartphone',
                'description' => 'Mobile phones, laptops, gadgets and tech accessories',
                'subs' => ['Smartphones', 'Laptops & Computers', 'Audio & Headphones', 'Cameras', 'Gaming'],
                'featured' => true,
                'image_seed' => 'electronics',
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'icon' => 'shirt',
                'description' => 'Clothing, footwear and accessories for everyone',
                'subs' => ['Men\'s Clothing', 'Women\'s Clothing', 'Footwear', 'Bags & Accessories', 'Jewelry'],
                'featured' => true,
                'image_seed' => 'fashion',
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-living',
                'icon' => 'home',
                'description' => 'Furniture, décor and household essentials',
                'subs' => ['Furniture', 'Home Décor', 'Kitchen', 'Bedding', 'Storage'],
                'featured' => true,
                'image_seed' => 'home',
            ],
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty',
                'icon' => 'sparkles',
                'description' => 'Skincare, makeup and personal grooming',
                'subs' => ['Skincare', 'Makeup', 'Hair Care', 'Fragrance', 'Men\'s Grooming'],
                'featured' => true,
                'image_seed' => 'beauty',
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports',
                'icon' => 'dumbbell',
                'description' => 'Fitness gear, outdoor equipment and activewear',
                'subs' => ['Fitness', 'Outdoor Gear', 'Cycling', 'Team Sports', 'Yoga'],
                'featured' => true,
                'image_seed' => 'sports',
            ],
            [
                'name' => 'Books & Stationery',
                'slug' => 'books',
                'icon' => 'book',
                'description' => 'Books, office supplies and educational materials',
                'subs' => ['Books', 'Office Supplies', 'Art Supplies', 'Educational', 'Notebooks'],
                'featured' => false,
                'image_seed' => 'books',
            ],
            [
                'name' => 'Toys & Kids',
                'slug' => 'toys-kids',
                'icon' => 'baby',
                'description' => 'Toys, baby gear and children\'s products',
                'subs' => ['Toys', 'Baby Care', 'Kids Clothing', 'Educational Toys', 'Outdoor Play'],
                'featured' => false,
                'image_seed' => 'toys',
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'icon' => 'car',
                'description' => 'Car accessories, parts and maintenance',
                'subs' => ['Car Accessories', 'Car Care', 'Electronics', 'Safety', 'Interior'],
                'featured' => false,
                'image_seed' => 'auto',
            ],
            [
                'name' => 'Health & Wellness',
                'slug' => 'health',
                'icon' => 'heart',
                'description' => 'Vitamins, supplements and wellness products',
                'subs' => ['Vitamins', 'Supplements', 'Medical', 'Wellness', 'Organic'],
                'featured' => false,
                'image_seed' => 'health',
            ],
            [
                'name' => 'Groceries & Food',
                'slug' => 'groceries',
                'icon' => 'shopping-cart',
                'description' => 'Fresh produce, pantry staples and beverages',
                'subs' => ['Fresh Produce', 'Pantry', 'Beverages', 'Snacks', 'Organic'],
                'featured' => false,
                'image_seed' => 'food',
            ],
        ];

        $categoryIds = [];
        $sortOrder = 0;

        foreach ($categoriesData as $catData) {
            $imagePath = null;
            $imgUrl = "https://picsum.photos/seed/{$catData['image_seed']}/400/300";
            $imagePath = $this->downloadImage($imgUrl, "categories/{$catData['slug']}.jpg");

            $parent = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'icon' => $catData['icon'],
                    'image' => $imagePath,
                    'parent_id' => null,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                    'is_featured' => $catData['featured'],
                ]
            );

            $categoryIds[$catData['slug']] = $parent->id;

            $subSort = 0;
            foreach ($catData['subs'] as $subName) {
                $subSlug = $catData['slug'] . '-' . Str::slug($subName);
                Category::updateOrCreate(
                    ['slug' => $subSlug],
                    [
                        'name' => $subName,
                        'description' => "Products in {$subName}",
                        'icon' => $catData['icon'],
                        'parent_id' => $parent->id,
                        'sort_order' => $subSort++,
                        'is_active' => true,
                        'is_featured' => false,
                    ]
                );
            }
        }

        // 3. Product data: 5 products per parent category (50 total)
        $productsByCategory = [
            'electronics' => [
                ['Wireless Bluetooth Earbuds Pro', 3499, 4499, 'Premium sound quality, 24hr battery', 'electronics-1'],
                ['Smart Watch Series 8', 12999, 15999, 'Health tracking, GPS, AMOLED display', 'electronics-2'],
                ['USB-C Fast Charger 65W', 1499, 1999, 'Multi-port, fast charging for all devices', 'electronics-3'],
                ['Wireless Gaming Mouse', 2499, 2999, 'RGB, 16000 DPI, ergonomic design', 'electronics-4'],
                ['Portable SSD 1TB', 8999, 11999, 'USB 3.2, rugged, 1050MB/s read', 'electronics-5'],
            ],
            'fashion' => [
                ['Classic Cotton Polo Shirt', 1499, 1999, 'Premium cotton, multiple colors', 'fashion-1'],
                ['Slim Fit Chino Pants', 2499, 3299, 'Comfortable stretch fabric', 'fashion-2'],
                ['Leather Casual Sneakers', 3999, 4999, 'Handcrafted, breathable lining', 'fashion-3'],
                ['Designer Crossbody Bag', 2999, 3999, 'Genuine leather, multiple compartments', 'fashion-4'],
                ['Premium Wool Blend Scarf', 999, 1299, 'Soft, warm, unisex design', 'fashion-5'],
            ],
            'home-living' => [
                ['Modern LED Desk Lamp', 1899, 2499, 'Adjustable brightness, USB charging', 'home-1'],
                ['Memory Foam Pillow Set', 2299, 2999, 'Ergonomic, hypoallergenic', 'home-2'],
                ['Ceramic Coffee Mug Set', 799, 999, 'Set of 4, microwave safe', 'home-3'],
                ['Wooden Bookshelf 5-Tier', 5999, 7499, 'Solid wood, easy assembly', 'home-4'],
                ['Luxury Bath Towel Set', 1999, 2599, 'Egyptian cotton, 6-piece set', 'home-5'],
            ],
            'beauty' => [
                ['Vitamin C Serum 30ml', 1899, 2499, 'Brightening, anti-aging formula', 'beauty-1'],
                ['Hydrating Face Moisturizer', 1299, 1699, 'SPF 30, lightweight, non-greasy', 'beauty-2'],
                ['Natural Bristle Hair Brush', 699, 899, 'Boar bristle, detangling', 'beauty-3'],
                ['Luxury Perfume 50ml', 3999, 4999, 'Long-lasting, premium fragrance', 'beauty-4'],
                ['Silicone Face Cleansing Brush', 999, 1299, 'USB rechargeable, 3 modes', 'beauty-5'],
            ],
            'sports' => [
                ['Adjustable Dumbbells 10kg Pair', 3499, 4499, 'Neoprene coating, non-slip grip', 'sports-1'],
                ['Yoga Mat 6mm Premium', 1299, 1699, 'Non-slip, eco-friendly TPE', 'sports-2'],
                ['Resistance Bands Set', 899, 1199, '5 levels, door anchor, bag included', 'sports-3'],
                ['Running Shoes Lightweight', 4999, 6499, 'Breathable mesh, cushioned sole', 'sports-4'],
                ['Fitness Tracker Smartwatch', 2999, 3999, 'Heart rate, sleep, 50m waterproof', 'sports-5'],
            ],
            'books' => [
                ['Hardcover Journal Set', 799, 999, '3-pack, ruled, premium paper', 'books-1'],
                ['Ballpoint Pen Gift Set', 499, 699, '12 colors, smooth writing', 'books-2'],
                ['Desk Organizer Bamboo', 1299, 1699, 'Multi-compartment, eco-friendly', 'books-3'],
                ['Sticky Notes Assorted', 299, 399, '6 colors, 5 sizes', 'books-4'],
                ['Leather Portfolio A4', 2499, 3299, 'Professional, document holder', 'books-5'],
            ],
            'toys-kids' => [
                ['Educational Building Blocks', 1499, 1999, '120 pieces, STEM learning', 'toys-1'],
                ['Soft Plush Teddy Bear', 699, 899, 'Premium quality, washable', 'toys-2'],
                ['Kids Art Supplies Kit', 999, 1299, 'Crayons, markers, paper', 'toys-3'],
                ['Outdoor Play Tent', 2499, 3199, 'Pop-up, foldable, for indoor/outdoor', 'toys-4'],
                ['Baby Feeding Set', 1299, 1699, 'BPA-free, 6-piece set', 'toys-5'],
            ],
            'automotive' => [
                ['Car Phone Holder Magnetic', 599, 799, 'Strong grip, 360° rotation', 'auto-1'],
                ['Car Air Freshener Pack', 299, 399, '6 fragrances, long-lasting', 'auto-2'],
                ['Microfiber Car Cloth Set', 499, 699, '3-pack, lint-free', 'auto-3'],
                ['Portable Tire Inflator', 2499, 3199, 'Digital display, LED light', 'auto-4'],
                ['Car Vacuum Cleaner', 1999, 2599, 'Cordless, rechargeable, handheld', 'auto-5'],
            ],
            'health' => [
                ['Multivitamin Daily 60 Tablets', 1299, 1699, 'Complete daily nutrition', 'health-1'],
                ['Omega-3 Fish Oil 90 Capsules', 1499, 1999, '1000mg, heart & brain health', 'health-2'],
                ['Digital Blood Pressure Monitor', 2499, 3199, 'Large display, irregular heartbeat detection', 'health-3'],
                ['Organic Green Tea 50 Bags', 499, 699, 'Antioxidant rich, caffeine', 'health-4'],
                ['Resistance Band Set', 799, 999, '5 levels, exercise guide', 'health-5'],
            ],
            'groceries' => [
                ['Extra Virgin Olive Oil 500ml', 999, 1299, 'Cold pressed, premium quality', 'grocery-1'],
                ['Organic Honey 500g', 799, 999, 'Pure, raw, no additives', 'grocery-2'],
                ['Mixed Nuts 500g Pack', 899, 1199, 'Roasted, salted, premium blend', 'grocery-3'],
                ['Granola Cereal 400g', 599, 799, 'Oats, honey, almonds', 'grocery-4'],
                ['Instant Coffee Jar 200g', 649, 849, 'Rich aroma, smooth taste', 'grocery-5'],
            ],
        ];

        $productCount = 0;
        foreach ($productsByCategory as $catSlug => $products) {
            $categoryId = $categoryIds[$catSlug] ?? null;
            if (!$categoryId) continue;

            foreach ($products as $p) {
                [$name, $price, $comparePrice, $shortDesc, $imgSeed] = $p;
                $slug = Str::slug($name) . '-' . $store->id . '-' . $productCount;

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'store_id' => $store->id,
                        'seller_id' => $user->id,
                        'seller_type' => 'business',
                        'category_id' => $categoryId,
                        'name' => $name,
                        'short_description' => $shortDesc,
                        'description' => "{$shortDesc}. High-quality product from Samsaf Tech. Trusted by thousands of customers across Pakistan. Fast delivery and easy returns.",
                        'status' => 'published',
                        'condition' => 'new',
                        'price' => $price,
                        'compare_at_price' => $comparePrice,
                        'quantity' => rand(20, 150),
                        'track_inventory' => true,
                        'is_featured' => $productCount < 15, // First 15 featured
                        'is_hot' => $productCount < 8,
                    ]
                );

                // Add product image
                $imgPath = "products/{$imgSeed}.jpg";
                $imgUrl = "https://picsum.photos/seed/{$imgSeed}/600/600";
                $savedPath = $this->downloadImage($imgUrl, $imgPath);

                if ($savedPath) {
                    ProductMedia::updateOrCreate(
                        ['product_id' => $product->id, 'sort_order' => 0],
                        [
                            'type' => 'image',
                            'path' => $savedPath,
                            'alt_text' => $name,
                            'is_thumbnail' => true,
                        ]
                    );
                }

                $productCount++;
            }
        }

        $this->command->info("Created 10 categories with 5 subcategories each. Added 50 products for samsaftech@gmail.com.");
    }
}
