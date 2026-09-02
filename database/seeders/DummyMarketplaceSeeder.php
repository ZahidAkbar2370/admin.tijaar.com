<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Commission;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Promotion;
use App\Models\PromotionPackage;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use App\Services\MarketplaceFeeService;
use App\Services\OrderFeeBreakdown;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Rich dummy marketplace data for local/staging demos and QA.
 *
 * Usage:
 *   php artisan db:seed --class=DummyMarketplaceSeeder
 *
 * Re-run safe: users are matched by email (dummy.customer* / dummy.seller*).
 * Private sellers use role `seller` + `is_private_seller` (C2C listings, KYC approved).
 * Default password for all dummy accounts: password
 */
class DummyMarketplaceSeeder extends Seeder
{
    public const PASSWORD = 'password';

    private const CUSTOMER_COUNT = 10;

    private const SELLER_COUNT = 10;

    /** @var array<int, User> */
    private array $customers = [];

    /** @var array<int, User> */
    private array $privateSellers = [];

    /** @var array<int, array<int, Product>> */
    private array $productsBySeller = [];

    public function run(): void
    {
        $this->command?->info('DummyMarketplaceSeeder: preparing prerequisites…');
        $this->ensurePrerequisites();

        $this->command?->info('Creating customers, private sellers, products…');
        $this->seedCustomers();
        $this->seedPrivateSellers();
        $this->seedProducts();
        $this->seedPromotions();

        $this->command?->info('Creating reviews, wallets, orders, chats…');
        $this->seedReviews();
        $this->seedWallets();
        $this->seedOrders();
        $this->seedConversations();

        $this->printSummary();
    }

    private function ensurePrerequisites(): void
    {
        $this->call([
            SettingSeeder::class,
            CommissionSeeder::class,
            PromotionPackageSeeder::class,
        ]);

        Commission::firstOrCreate(
            [
                'scope_type' => 'seller_type',
                'seller_type' => 'private',
            ],
            [
                'scope_id' => null,
                'commission_type' => 'percentage',
                'value' => 2,
                'priority' => 5,
                'is_active' => true,
            ]
        );

        if (Category::whereNull('parent_id')->count() === 0) {
            $this->call(TijaarDataSeeder::class);
        }

        if (Category::whereNull('parent_id')->count() === 0) {
            $names = ['Electronics', 'Fashion', 'Home & Living', 'Sports', 'Beauty'];
            foreach ($names as $i => $name) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'description' => "{$name} products",
                        'parent_id' => null,
                        'sort_order' => $i,
                        'is_active' => true,
                        'is_featured' => $i < 3,
                    ]
                );
            }
        }
    }

    private function pkLocations(): array
    {
        return [
            ['city' => 'Karachi', 'state' => 'Sindh'],
            ['city' => 'Lahore', 'state' => 'Punjab'],
            ['city' => 'Islamabad', 'state' => 'Islamabad Capital Territory'],
            ['city' => 'Faisalabad', 'state' => 'Punjab'],
            ['city' => 'Rawalpindi', 'state' => 'Punjab'],
            ['city' => 'Multan', 'state' => 'Punjab'],
            ['city' => 'Peshawar', 'state' => 'KPK'],
            ['city' => 'Quetta', 'state' => 'Balochistan'],
            ['city' => 'Hyderabad', 'state' => 'Sindh'],
            ['city' => 'Sialkot', 'state' => 'Punjab'],
        ];
    }

    private function seedCustomers(): void
    {
        $locations = $this->pkLocations();
        $firstNames = ['Ali', 'Sara', 'Usman', 'Ayesha', 'Hamza', 'Fatima', 'Bilal', 'Zainab', 'Omar', 'Hira'];
        $lastNames = ['Khan', 'Ahmed', 'Malik', 'Hussain', 'Sheikh', 'Raza', 'Iqbal', 'Butt', 'Mirza', 'Ansari'];

        for ($i = 1; $i <= self::CUSTOMER_COUNT; $i++) {
            $loc = $locations[($i - 1) % count($locations)];
            $phone = '03' . str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT);
            $email = "dummy.customer{$i}@tijaar.test";

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $firstNames[$i - 1] . ' ' . $lastNames[$i - 1],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'customer',
                    'phone' => $phone,
                    'city' => $loc['city'],
                    'state' => $loc['state'],
                    'email_verified_at' => now()->subDays(rand(10, 90)),
                    'phone_verified_at' => now()->subDays(rand(5, 60)),
                    'is_private_seller' => false,
                    'private_seller_kyc_status' => null,
                ]
            );

            Address::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'shipping',
                    'label' => 'Home',
                ],
                [
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name)[1] ?? 'Customer',
                    'phone' => $phone,
                    'address_line_1' => "House {$i}, Block " . chr(64 + $i) . ', Street ' . ($i * 3),
                    'address_line_2' => $loc['city'] . ' Central',
                    'city' => $loc['city'],
                    'state' => $loc['state'],
                    'country' => 'Pakistan',
                    'zip_code' => (string) (74000 + $i),
                    'is_default' => true,
                ]
            );

            Address::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'billing',
                    'label' => 'Billing',
                ],
                [
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name)[1] ?? 'Customer',
                    'phone' => $phone,
                    'address_line_1' => "House {$i}, Block " . chr(64 + $i) . ', Street ' . ($i * 3),
                    'city' => $loc['city'],
                    'state' => $loc['state'],
                    'country' => 'Pakistan',
                    'zip_code' => (string) (74000 + $i),
                    'is_default' => false,
                ]
            );

            $this->customers[$i] = $user;
        }
    }

    private function seedPrivateSellers(): void
    {
        $locations = $this->pkLocations();
        $banks = ['HBL', 'MCB Bank', 'UBL', 'Meezan Bank', 'Allied Bank'];

        for ($i = 1; $i <= self::SELLER_COUNT; $i++) {
            $loc = $locations[($i - 1) % count($locations)];
            $phone = '03' . str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT);
            $email = "dummy.seller{$i}@tijaar.test";
            $name = "Private Seller {$i}";

            $kyc = [
                'cnic' => '35202-' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT) . '-1',
                'document_type' => 'govt_id',
                'phone' => $phone,
                'address' => "{$i} Seller Lane, {$loc['city']}",
                'city' => $loc['city'],
                'bank_name' => $banks[$i % count($banks)],
                'bank_account_number' => 'PK' . (1000000000 + $i),
                'bank_account_holder' => $name,
                'submitted_at' => now()->subDays(40)->toIso8601String(),
                'approved_at' => now()->subDays(35)->toIso8601String(),
            ];

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'seller',
                    'registration_source' => 'web',
                    'phone' => $phone,
                    'whatsapp_number' => $phone,
                    'city' => $loc['city'],
                    'state' => $loc['state'],
                    'email_verified_at' => now()->subDays(rand(30, 120)),
                    'phone_verified_at' => now()->subDays(rand(20, 100)),
                    'whatsapp_verified_at' => now()->subDays(rand(15, 90)),
                    'is_private_seller' => true,
                    'private_seller_kyc_status' => 'approved',
                    'private_listing_limit' => 25,
                    'preferences' => ['private_seller_kyc' => $kyc],
                ]
            );

            $seller = Seller::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 'approved',
                    'kyc_status' => 'verified',
                    'bank_name' => $kyc['bank_name'],
                    'bank_account_number' => $kyc['bank_account_number'],
                    'bank_account_holder' => $kyc['bank_account_holder'],
                    'approved_at' => now()->subDays(35),
                ]
            );

            Store::firstOrCreate(
                ['seller_id' => $seller->id],
                [
                    'name' => $name . ' Shop',
                    'slug' => Str::slug($name . '-shop-' . $user->id),
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $kyc['address'],
                    'city' => $loc['city'],
                    'state' => $loc['state'],
                    'country' => 'Pakistan',
                    'is_active' => true,
                ]
            );

            $this->privateSellers[$i] = $user->fresh(['seller.store']);
        }
    }

    private function productTemplates(): array
    {
        return [
            ['Wireless Earbuds', 2499, 3299, 'Noise cancelling, 24h battery'],
            ['Smart Watch Band', 899, 1199, 'Silicone strap, universal fit'],
            ['Phone Stand Holder', 499, 699, 'Adjustable desk mount'],
            ['USB-C Cable 2m', 399, 549, 'Fast charge supported'],
            ['Portable Power Bank', 1999, 2599, '10000mAh, dual USB'],
            ['Cotton T-Shirt', 899, 1199, 'Soft fabric, multiple sizes'],
            ['Denim Jacket', 3499, 4499, 'Classic fit, durable'],
            ['Running Shoes', 3999, 5499, 'Lightweight, cushioned sole'],
            ['Leather Wallet', 1299, 1699, 'Genuine leather, RFID block'],
            ['Sunglasses UV400', 999, 1399, 'Polarized lenses'],
            ['Ceramic Mug Set', 799, 999, 'Set of 4, microwave safe'],
            ['LED Desk Lamp', 1899, 2499, '3 brightness levels'],
            ['Throw Pillow Cover', 599, 799, 'Cotton blend, zip closure'],
            ['Storage Basket', 899, 1099, 'Woven, multi-purpose'],
            ['Kitchen Knife Set', 2499, 3199, 'Stainless steel, 5 pieces'],
            ['Yoga Mat 6mm', 1299, 1699, 'Non-slip TPE material'],
            ['Resistance Bands', 799, 999, '5 levels with carry bag'],
            ['Water Bottle 1L', 699, 899, 'BPA-free, leak proof'],
            ['Face Moisturizer', 1199, 1499, 'SPF 30, lightweight'],
            ['Hair Oil 100ml', 599, 799, 'Natural ingredients'],
        ];
    }

    private function seedProducts(): void
    {
        $this->migratePrivateSellerProductsToBusiness();

        $categories = Category::where('is_active', true)->orderBy('sort_order')->pluck('id')->all();
        if (empty($categories)) {
            $categories = Category::pluck('id')->all();
        }

        $templates = $this->productTemplates();

        foreach ($this->privateSellers as $sellerIndex => $seller) {
            $seller->loadMissing('seller.store');
            $storeId = $seller->seller?->store?->id;
            $count = rand(10, 20);
            $this->productsBySeller[$sellerIndex] = [];

            for ($p = 0; $p < $count; $p++) {
                $tpl = $templates[($sellerIndex + $p) % count($templates)];
                [$baseName, $price, $compare, $shortDesc] = $tpl;
                $name = $baseName . ' — Seller ' . $sellerIndex . '-' . ($p + 1);
                $slug = Str::slug($name) . '-ps' . $seller->id . '-' . $p;

                $isFeatured = $p < 2;
                $isHot = $p === 0 || $p === 3;

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'seller_type' => 'business',
                        'seller_id' => $seller->id,
                        'store_id' => $storeId,
                        'category_id' => $categories[($sellerIndex + $p) % count($categories)],
                        'sku' => 'PVT-DMY-' . $seller->id . '-' . str_pad((string) ($p + 1), 3, '0', STR_PAD_LEFT),
                        'name' => $name,
                        'short_description' => $shortDesc,
                        'description' => "{$shortDesc}. Quality pre-owned / new item listed by {$seller->name}. Ships from Pakistan with careful packaging.",
                        'status' => 'published',
                        'condition' => ['new', 'like_new', 'good'][$p % 3],
                        'price' => $price + ($sellerIndex * 50),
                        'compare_at_price' => $compare + ($sellerIndex * 50),
                        'quantity' => rand(100, 350),
                        'track_inventory' => true,
                        'shipping_mode' => 'customer_pays',
                        'shipping_cost_cached' => rand(150, 450),
                        'weight_kg' => round(rand(3, 20) / 10, 1),
                        'is_featured' => $isFeatured,
                        'is_hot' => $isHot,
                        'is_new_arrival' => $p < 4,
                        'flash_deal_discount_type' => $isHot ? 'percentage' : null,
                        'flash_deal_discount_value' => $isHot ? 10 : null,
                        'flash_deal_ends_at' => $isHot ? now()->addDays(14) : null,
                        'expires_at' => now()->addDays(30),
                        'product_type' => 'simple',
                    ]
                );

                $imgSeed = "dummy-{$seller->id}-{$p}";
                $imgPath = "products/{$imgSeed}.jpg";
                $this->ensureProductImage($imgPath, $imgSeed);

                ProductMedia::updateOrCreate(
                    ['product_id' => $product->id, 'sort_order' => 0],
                    [
                        'type' => 'image',
                        'path' => $imgPath,
                        'alt_text' => $name,
                        'is_thumbnail' => true,
                    ]
                );

                if (! $product->thumbnail_path) {
                    $product->update(['thumbnail_path' => $imgPath, 'thumbnail_alt' => $name]);
                }

                $this->productsBySeller[$sellerIndex][] = $product;
            }
        }
    }

    private function ensureProductImage(string $path, string $seed): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->makeDirectory(dirname($path));

        $shared = 'products/_dummy_placeholder.jpg';
        if (! Storage::disk('public')->exists($shared)) {
            try {
                $response = Http::timeout(5)->get('https://picsum.photos/seed/tijaar-dummy/600/600');
                if ($response->successful()) {
                    Storage::disk('public')->put($shared, $response->body());
                }
            } catch (\Throwable) {
                // offline: leave placeholder missing; UI falls back to sample image
            }
        }

        if (Storage::disk('public')->exists($shared)) {
            Storage::disk('public')->copy($shared, $path);
        }
    }

    private function seedPromotions(): void
    {
        $featuredPkg = PromotionPackage::where('type', 'featured_product')->first();
        $hotPkg = PromotionPackage::where('type', 'hot_sale')->first();

        if (! $featuredPkg || ! $hotPkg) {
            return;
        }

        foreach ($this->productsBySeller as $sellerIndex => $products) {
            $seller = $this->privateSellers[$sellerIndex];

            foreach ($products as $idx => $product) {
                if (! $product->is_featured && ! $product->is_hot) {
                    continue;
                }

                $pkg = $product->is_hot ? $hotPkg : $featuredPkg;

                Promotion::updateOrCreate(
                    [
                        'promotion_package_id' => $pkg->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'user_id' => $seller->id,
                        'starts_at' => now()->subDays(2),
                        'ends_at' => now()->addDays(21),
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'paid_by' => 'admin',
                    ]
                );
            }
        }
    }

    private function seedReviews(): void
    {
        $titles = [
            'Great value', 'Exactly as described', 'Fast seller response', 'Good quality', 'Happy with purchase',
            'Would buy again', 'Nice packaging', 'Recommended', 'Solid product', 'Five stars from me',
        ];
        $bodies = [
            'Item matched the photos and arrived in good condition.',
            'Seller was responsive and shipping was quick.',
            'Quality is better than I expected for the price.',
            'Smooth transaction — no issues at all.',
            'Perfect for everyday use. Very satisfied.',
        ];

        $reviewCount = 0;
        foreach ($this->productsBySeller as $products) {
            foreach ($products as $product) {
                if ($reviewCount > 120) {
                    break 2;
                }
                $customer = $this->customers[array_rand($this->customers)];
                Review::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'reviewable_type' => Product::class,
                        'reviewable_id' => $product->id,
                    ],
                    [
                        'rating' => rand(4, 5),
                        'title' => $titles[array_rand($titles)],
                        'body' => $bodies[array_rand($bodies)],
                        'is_verified_purchase' => (bool) rand(0, 1),
                        'status' => rand(0, 10) > 1 ? 'approved' : 'pending',
                        'helpful_count' => rand(0, 12),
                    ]
                );
                $reviewCount++;
            }
        }
    }

    private function seedWallets(): void
    {
        foreach ($this->customers as $customer) {
            $wallet = Wallet::getOrCreateForUser($customer->id, 'PKR');
            $balance = round(rand(3000, 15000) + (rand(0, 99) / 100), 2);

            $deposit = WalletDeposit::updateOrCreate(
                [
                    'user_id' => $customer->id,
                    'gateway_reference' => 'DUMMY-JC-' . $customer->id,
                ],
                [
                    'amount' => $balance,
                    'currency' => 'PKR',
                    'gateway' => 'jazzcash',
                    'status' => 'completed',
                    'paid_at' => now()->subDays(rand(5, 30)),
                ]
            );

            $wallet->update(['balance' => $balance]);

            WalletTransaction::firstOrCreate(
                [
                    'wallet_id' => $wallet->id,
                    'reference_type' => 'wallet_deposit',
                    'reference_id' => $deposit->id,
                    'type' => 'deposit',
                ],
                [
                    'amount' => $balance,
                    'balance_after' => $balance,
                    'description' => 'Dummy wallet top-up (JazzCash)',
                ]
            );
        }

        foreach ($this->privateSellers as $seller) {
            Wallet::getOrCreateForUser($seller->id, 'PKR');
        }
    }

    private function seedOrders(): void
    {
        $existingDummyOrders = Order::whereHas('user', fn ($q) => $q->where('email', 'like', 'dummy.customer%'))->count();
        if ($existingDummyOrders >= 20) {
            $this->command?->warn("Skipping orders — {$existingDummyOrders} dummy customer orders already exist.");

            return;
        }

        $statuses = [
            ['status' => 'completed', 'payment_status' => 'paid', 'fulfillment' => 'delivered', 'weight' => 35],
            ['status' => 'delivered', 'payment_status' => 'paid', 'fulfillment' => 'delivered', 'weight' => 20],
            ['status' => 'shipped', 'payment_status' => 'paid', 'fulfillment' => 'shipped', 'weight' => 15],
            ['status' => 'processing', 'payment_status' => 'paid', 'fulfillment' => 'approved', 'weight' => 12],
            ['status' => 'pending', 'payment_status' => 'pending', 'fulfillment' => 'pending', 'weight' => 8],
            ['status' => 'cancelled', 'payment_status' => 'pending', 'fulfillment' => 'cancelled', 'weight' => 5],
        ];

        $methods = ['cod', 'jazzcash', 'wallet', 'easypaisa'];
        $ordersCreated = 0;
        $targetOrders = 22;

        while ($ordersCreated < $targetOrders) {
            $customer = $this->customers[array_rand($this->customers)];
            $sellerIndex = array_rand($this->privateSellers);
            $seller = $this->privateSellers[$sellerIndex];
            $products = $this->productsBySeller[$sellerIndex] ?? [];
            if (empty($products)) {
                continue;
            }

            $product = $products[array_rand($products)];
            $qty = rand(1, 3);
            $shipping = (float) ($product->shipping_cost_cached ?? rand(150, 400));
            $paymentMethod = $methods[array_rand($methods)];

            $statusRow = $this->weightedRandom($statuses);
            if ($paymentMethod === 'cod' && $statusRow['status'] === 'pending') {
                // COD pending is valid
            } elseif ($statusRow['payment_status'] === 'paid' && $paymentMethod === 'cod') {
                $paymentMethod = 'jazzcash';
            }

            $address = $customer->addresses()->where('type', 'shipping')->first()
                ?? $customer->addresses()->first();

            if (! $address) {
                continue;
            }

            $createdAt = now()->subDays(rand(1, 45))->subHours(rand(0, 23));

            DB::transaction(function () use (
                $customer,
                $seller,
                $product,
                $qty,
                $shipping,
                $paymentMethod,
                $statusRow,
                $address,
                $createdAt,
                &$ordersCreated
            ) {
                $itemSubtotal = round((float) $product->price * $qty, 2);
                $discount = 0.0;

                $feeBreakdown = MarketplaceFeeService::customerTotal(
                    $itemSubtotal,
                    $shipping,
                    $discount,
                    $paymentMethod
                );

                $marketplaceFee = (float) $feeBreakdown['marketplace_fee'];
                $onlineFee = (float) $feeBreakdown['online_transaction_fee'];
                $total = (float) $feeBreakdown['total'];

                $effectiveSubtotal = max(0, $itemSubtotal - $discount);
                $storeId = $product->store_id;
                $commission = Commission::calculateFor(
                    $effectiveSubtotal,
                    $storeId,
                    $product->category_id,
                    'business'
                );

                $mpAlloc = 0.0;
                $otAlloc = 0.0;

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $customer->id,
                    'status' => $statusRow['status'] === 'delivered' ? 'completed' : $statusRow['status'],
                    'delivered_at' => in_array($statusRow['status'], ['completed', 'delivered'], true)
                        ? $createdAt->copy()->addDays(rand(3, 10))
                        : null,
                    'market' => 'PK',
                    'shipping_address_id' => $address->id,
                    'shipping_method' => 'tcs',
                    'shipping_cost' => $shipping,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => 0,
                    'discount_amount' => $discount,
                    'marketplace_fee' => $marketplaceFee,
                    'online_transaction_fee' => $onlineFee,
                    'marketplace_fee_type' => $feeBreakdown['marketplace_fee_type'],
                    'marketplace_fee_rate' => $feeBreakdown['marketplace_fee_value'],
                    'online_transaction_fee_type' => $feeBreakdown['online_transaction_fee_type'],
                    'online_transaction_fee_rate' => $feeBreakdown['online_transaction_fee_value'],
                    'seller_commission_total' => $commission,
                    'seller_marketplace_fee_total' => 0,
                    'seller_online_transaction_fee_total' => 0,
                    'platform_revenue' => round($marketplaceFee + $onlineFee + $commission, 2),
                    'total' => $total,
                    'online_amount' => $paymentMethod === 'cod' ? 0 : $total,
                    'cod_amount' => $paymentMethod === 'cod' ? $total : 0,
                    'partial_payment_percent' => null,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $statusRow['payment_status'],
                    'customer_notes' => rand(0, 1) ? 'Please call before delivery.' : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                OrderFeeBreakdown::snapshotSellerFeeMeta($order, false, true);

                OrderTimeline::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'note' => 'Order created',
                    'created_at' => $createdAt,
                ]);

                if ($statusRow['payment_status'] === 'paid') {
                    OrderTimeline::create([
                        'order_id' => $order->id,
                        'status' => 'paid',
                        'note' => 'Payment received',
                        'created_at' => $createdAt->copy()->addMinutes(30),
                    ]);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'seller_id' => $seller->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image_path' => $product->thumbnail_path,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'commission_amount' => $commission,
                    'marketplace_fee_allocated' => $mpAlloc,
                    'online_transaction_fee_allocated' => $otAlloc,
                    'discount_allocated' => $discount,
                    'seller_type' => 'business',
                    'fulfillment_status' => $statusRow['fulfillment'],
                    'approved_at' => in_array($statusRow['fulfillment'], ['approved', 'shipped', 'delivered'], true)
                        ? $createdAt->copy()->addDay()
                        : null,
                    'options' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if ($statusRow['payment_status'] === 'paid') {
                    Payment::create([
                        'order_id' => $order->id,
                        'gateway' => $paymentMethod === 'wallet' ? 'wallet' : ($paymentMethod === 'cod' ? 'cod' : $paymentMethod),
                        'amount' => $total,
                        'currency' => 'PKR',
                        'status' => 'completed',
                        'gateway_reference' => 'DUMMY-PAY-' . $order->id,
                        'paid_at' => $createdAt->copy()->addMinutes(20),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                if ($paymentMethod === 'wallet' && $statusRow['payment_status'] === 'paid') {
                    $wallet = Wallet::getOrCreateForUser($customer->id, 'PKR');
                    $newBal = max(0, round((float) $wallet->balance - $total, 2));
                    $wallet->update(['balance' => $newBal]);
                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'order_payment',
                        'amount' => -$total,
                        'balance_after' => $newBal,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'description' => 'Order #' . $order->order_number,
                        'created_at' => $createdAt->copy()->addMinutes(20),
                    ]);
                }

                if (in_array($statusRow['fulfillment'], ['shipped', 'delivered'], true)) {
                    Shipment::create([
                        'order_id' => $order->id,
                        'store_id' => $storeId,
                        'seller_id' => $seller->id,
                        'shipping_cost' => $shipping,
                        'carrier' => rand(0, 1) ? 'tcs' : 'leopards',
                        'tracking_number' => 'CN' . rand(100000000, 999999999),
                        'status' => $statusRow['fulfillment'] === 'delivered' ? 'delivered' : 'shipped',
                        'shipped_at' => $createdAt->copy()->addDays(2),
                        'delivered_at' => $statusRow['fulfillment'] === 'delivered'
                            ? $createdAt->copy()->addDays(rand(4, 8))
                            : null,
                        'created_at' => $createdAt->copy()->addDays(2),
                    ]);
                }

                if (in_array($statusRow['status'], ['completed', 'delivered'], true)
                    && $statusRow['payment_status'] === 'paid') {
                    $sellerWallet = Wallet::getOrCreateForUser($seller->id, 'PKR');
                    $net = MarketplaceFeeService::sellerLineNet(
                        (float) $qty,
                        (float) $product->price,
                        $discount,
                        $commission,
                        $mpAlloc,
                        $otAlloc
                    );
                    if ($net > 0) {
                        $newSellerBal = round((float) $sellerWallet->balance + $net, 2);
                        $sellerWallet->update(['balance' => $newSellerBal]);
                        WalletTransaction::create([
                            'wallet_id' => $sellerWallet->id,
                            'type' => 'order_payment',
                            'amount' => $net,
                            'balance_after' => $newSellerBal,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'description' => 'Earnings for order #' . $order->order_number,
                            'created_at' => $createdAt->copy()->addDays(8),
                        ]);
                    }
                }

                $ordersCreated++;
            });
        }
    }

    private function seedConversations(): void
    {
        $openers = [
            'Hi, is this item still available?',
            'Can you share more photos?',
            'Would you accept PKR {price}?',
            'What is the earliest delivery time?',
            'Is pickup possible in {city}?',
        ];
        $replies = [
            'Yes, it is available and ready to ship.',
            'Sure — it is in excellent condition.',
            'I can ship tomorrow via TCS.',
            'Price is slightly firm but I can include free shipping.',
            'Thanks for your interest!',
        ];

        $chatCount = 0;
        foreach ($this->customers as $customer) {
            if ($chatCount >= 12) {
                break;
            }

            $sellerIndex = array_rand($this->privateSellers);
            $seller = $this->privateSellers[$sellerIndex];
            $products = $this->productsBySeller[$sellerIndex] ?? [];
            if (empty($products)) {
                continue;
            }
            $product = $products[array_rand($products)];

            $conversation = Conversation::firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'seller_id' => $seller->id,
                    'product_id' => $product->id,
                ],
                [
                    'subject' => 'Question about ' . Str::limit($product->name, 40),
                ]
            );

            $started = now()->subDays(rand(1, 20));
            $msgBodies = [
                str_replace(['{price}', '{city}'], [(string) $product->price, $customer->city ?? 'Karachi'], $openers[array_rand($openers)]),
                $replies[array_rand($replies)],
                'Great, I will place the order soon.',
                'Perfect — let me know when you ship.',
            ];

            foreach ($msgBodies as $i => $body) {
                Message::updateOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'user_id' => $i % 2 === 0 ? $customer->id : $seller->id,
                        'body' => $body,
                    ],
                    [
                        'type' => 'text',
                        'created_at' => $started->copy()->addMinutes($i * 45),
                        'updated_at' => $started->copy()->addMinutes($i * 45),
                    ]
                );
            }

            $chatCount++;
        }
    }

    /** Re-tag legacy private-seller listings as business + store (idempotent). */
    private function migratePrivateSellerProductsToBusiness(): void
    {
        foreach ($this->privateSellers as $seller) {
            $seller->loadMissing('seller.store');
            $storeId = $seller->seller?->store?->id;
            if (! $storeId) {
                continue;
            }

            Product::query()
                ->where('seller_id', $seller->id)
                ->where('seller_type', 'private')
                ->update([
                    'seller_type' => 'business',
                    'store_id' => $storeId,
                ]);

            OrderItem::query()
                ->where('seller_id', $seller->id)
                ->where('seller_type', 'private')
                ->update([
                    'seller_type' => 'business',
                    'store_id' => $storeId,
                ]);
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function weightedRandom(array $rows): array
    {
        $total = array_sum(array_column($rows, 'weight'));
        $pick = rand(1, max(1, $total));
        $running = 0;
        foreach ($rows as $row) {
            $running += (int) $row['weight'];
            if ($pick <= $running) {
                return $row;
            }
        }

        return $rows[0];
    }

    private function printSummary(): void
    {
        $productTotal = array_sum(array_map('count', $this->productsBySeller));

        $this->command?->newLine();
        $this->command?->info('DummyMarketplaceSeeder finished.');
        $this->command?->table(
            ['Item', 'Count'],
            [
                ['Customers', count($this->customers)],
                ['Private sellers (KYC approved)', count($this->privateSellers)],
                ['Private listing products', $productTotal],
                ['Reviews', Review::where('reviewable_type', Product::class)->count()],
                ['Orders (dummy.*)', Order::whereHas('user', fn ($q) => $q->where('email', 'like', 'dummy.customer%'))->count()],
                ['Conversations', Conversation::count()],
            ]
        );
        $this->command?->info('Login password for all dummy accounts: ' . self::PASSWORD);
        $this->command?->line('Customers: dummy.customer1@tijaar.test … dummy.customer10@tijaar.test');
        $this->command?->line('Sellers:   dummy.seller1@tijaar.test … dummy.seller10@tijaar.test (role: seller, is_private_seller: true)');
    }
}
