<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::with('seller')->get();
        if ($stores->isEmpty()) return;

        $defaultCategory = Category::first();
        if (!$defaultCategory) {
            $defaultCategory = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        }

        $products = [
            ['Wireless Bluetooth Earbuds', 2999], ['Smart Watch Pro', 8999], ['Cotton Summer Dress', 2499], ['Wooden Study Desk', 12500],
            ['Organic Face Cream', 1599], ['LED Desk Lamp', 1899], ['Portable Power Bank', 1299], ['Leather Handbag', 4599],
            ['Stainless Steel Water Bottle', 799], ['Running Shoes', 3499], ['Wireless Mouse', 999], ['Mechanical Keyboard', 4499],
            ['USB-C Hub', 1999], ['Phone Stand', 499], ['Webcam HD', 2499], ['Bluetooth Speaker', 1799], ['Coffee Maker', 5999],
            ['Yoga Mat', 1299], ['Resistance Bands Set', 899], ['Dumbbells 5kg Pair', 2499], ['Sleep Mask', 399],
            ['Desk Organizer', 699], ['Notebook Set', 499], ['Pen Holder', 299], ['Monitor Arm', 3499],
            ['Laptop Stand', 1299], ['Ergonomic Chair', 12999], ['Standing Desk', 18999], ['Cable Management', 599],
            ['Screen Cleaner Kit', 299], ['Phone Case', 599], ['Tablet Sleeve', 899], ['Laptop Bag', 1999],
            ['Wrist Rest', 499], ['Foot Rest', 899], ['Blue Light Glasses', 1299], ['Air Purifier', 4999],
            ['Humidifier', 2499], ['Smart Bulb', 799], ['Doorbell Camera', 6999], ['Fitness Tracker', 2999],
            ['Electric Toothbrush', 1999], ['Hair Dryer', 1599], ['Electric Shaver', 2499], ['Trimmer', 999],
            ['Sunglasses', 1499], ['Watch Strap', 599], ['Backpack', 2799], ['Tote Bag', 1299],
            ['Wallet', 999], ['Belt', 799], ['Scarf', 699], ['Winter Gloves', 599],
            // 15 more products
            ['Wireless Gaming Headset', 4499], ['External SSD 1TB', 9999], ['Electric Kettle', 2499],
            ['Noise Cancelling Headphones', 8999], ['Mini Projector', 14999], ['Action Camera', 12999],
            ['Wireless Charger Pad', 1499], ['Smart Scale', 1999], ['Posture Corrector', 899],
            ['Memory Foam Pillow', 2299], ['Aromatherapy Diffuser', 1299], ['Desk Fan', 999],
            ['Card Holder', 499], ['Passport Cover', 699], ['Travel Pillow', 799],
        ];

        foreach ($products as $i => $p) {
            $store = $stores->get($i % $stores->count());
            $slug = \Illuminate\Support\Str::slug($p[0]) . '-' . $store->id . '-' . $i;
            $price = $p[1];
            $compareAt = $i < 18 ? (int) ($price * (1.2 + (rand(1, 15) / 100))) : null; // First 18 have a "was" price for deals
            $product = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'store_id' => $store->id,
                    'category_id' => $defaultCategory->id,
                    'name' => $p[0],
                    'description' => 'High quality product. ' . $p[0],
                    'status' => 'published',
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'quantity' => rand(20, 150),
                    'is_featured' => $i < 12,
                ]
            );
            // Ensure existing products get stock and deal/featured flags when seeder re-runs
            if ($product->wasRecentlyCreated === false) {
                $updates = ['quantity' => rand(20, 150)];
                if ($i < 18 && !$product->compare_at_price) {
                    $updates['compare_at_price'] = (int) ($price * (1.2 + (rand(1, 15) / 100)));
                }
                if ($i < 12) {
                    $updates['is_featured'] = true;
                }
                $product->update($updates);
            }
        }

        // Round Neck Red TShirt New – with variations (Size, Color)
        $tshirtName = 'Round Neck Red TShirt New';
        $tshirtSlug = \Illuminate\Support\Str::slug($tshirtName);
        $store = $stores->first();
        $tshirt = Product::firstOrCreate(
            ['slug' => $tshirtSlug],
            [
                'store_id' => $store->id,
                'category_id' => $defaultCategory->id,
                'name' => $tshirtName,
                'description' => 'Comfortable round neck t-shirt in red. Made from soft cotton. Perfect for casual wear.',
                'short_description' => 'Soft cotton. Round neck. Red color. Available in S, M, L, XL. Multiple colors.',
                'status' => 'published',
                'price' => 1499,
                'compare_at_price' => 1999,
                'quantity' => rand(50, 120),
                'track_inventory' => true,
                'condition' => 'new',
                'is_hot' => true,
            ]
        );
        if ($tshirt->wasRecentlyCreated === false && ($tshirt->quantity <= 0 || $tshirt->quantity === null)) {
            $tshirt->update(['quantity' => rand(50, 120)]);
        }

        $sizes = ['S', 'M', 'L', 'XL'];
        $colors = ['Red', 'Navy', 'White'];
        $basePrice = 1499;
        $vIndex = 0;
        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $name = "{$color} / {$size}";
                $sku = 'TEE-' . strtoupper(substr($color, 0, 1)) . '-' . $size . '-' . $tshirt->id;
                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $tshirt->id,
                        'sku' => $sku,
                    ],
                    [
                        'name' => $name,
                        'attributes' => ['size' => $size, 'color' => $color],
                        'price' => $basePrice + ($size === 'XL' ? 100 : 0),
                        'compare_at_price' => 1999,
                        'quantity' => rand(15, 50),
                    ]
                );
                if ($variant->wasRecentlyCreated === false && ($variant->quantity <= 0 || $variant->quantity === null)) {
                    $variant->update(['quantity' => rand(15, 50)]);
                }
                $vIndex++;
            }
        }

        // Ensure any other "Round Neck Red TShirt New" product (e.g. slug round-neck-red-tshirt-new-77) has variants
        $sizes = ['S', 'M', 'L', 'XL'];
        $colors = ['Red', 'Navy', 'White'];
        $basePrice = 1499;
        $others = Product::where(function ($q) use ($tshirtName) {
            $q->where('name', 'like', '%' . $tshirtName . '%')
                ->orWhere('slug', 'like', \Illuminate\Support\Str::slug($tshirtName) . '%');
        })
            ->where('id', '!=', $tshirt->id)
            ->published()
            ->get();
        foreach ($others as $product) {
            if ($product->variants()->count() > 0) {
                continue;
            }
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $name = "{$color} / {$size}";
                    $sku = 'TEE-' . strtoupper(substr($color, 0, 1)) . '-' . $size . '-' . $product->id;
                    ProductVariant::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'sku' => $sku,
                        ],
                        [
                            'name' => $name,
                            'attributes' => ['size' => $size, 'color' => $color],
                            'price' => $basePrice + ($size === 'XL' ? 100 : 0),
                            'compare_at_price' => 1999,
                            'quantity' => rand(15, 50),
                        ]
                    );
                }
            }
        }
    }
}
