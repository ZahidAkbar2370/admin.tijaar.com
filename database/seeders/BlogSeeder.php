<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Seed 5 sample blogs. Run: php artisan db:seed --class=BlogSeeder
     */
    public function run(): void
    {
        $authorId = User::first()?->id;

        $blogs = [
            [
                'title' => '5 Tips for Buying Safely on Tijaar',
                'slug' => '5-tips-for-buying-safely-on-tijaar',
                'excerpt' => 'Learn how to shop with confidence on our marketplace. From checking seller ratings to secure payment—here are five simple tips every buyer should know.',
                'content' => '<p>Shopping on a marketplace can feel overwhelming at first. At Tijaar, we want you to feel confident with every purchase. Here are five tips to help you buy safely.</p><h2>1. Check Seller Ratings & Reviews</h2><p>Before you buy, take a moment to look at the seller’s profile. Read recent reviews and check their rating. Sellers with a track record of happy customers are a good bet.</p><h2>2. Read the Product Description</h2><p>Make sure the item matches what you need. Check size, condition, and any important details. If something is unclear, message the seller before ordering.</p><h2>3. Use Secure Payment</h2><p>Always pay through Tijaar’s checkout. This keeps your payment secure and helps you get support if something goes wrong. Avoid paying outside the platform.</p><h2>4. Keep Order Records</h2><p>Save your order confirmation and any messages with the seller. If you need to open a dispute or return an item, this information will help.</p><h2>5. Know the Return Policy</h2><p>Each seller may have different return rules. Check the product page and seller profile so you know what to expect. You can also visit our <a href="/returns-refunds">Returns & Refunds</a> page for more info.</p><p>Happy shopping! If you have questions, our <a href="/help">Help Center</a> and <a href="/contact">Contact</a> team are here for you.</p>',
                'meta_title' => '5 Tips for Buying Safely on Tijaar',
                'meta_description' => 'Simple tips to shop safely on Tijaar: check sellers, read descriptions, use secure payment, and know the return policy.',
                'is_published' => true,
                'published_at' => now()->subDays(12),
            ],
            [
                'title' => 'How to Start Selling on Tijaar: A Quick Guide',
                'slug' => 'how-to-start-selling-on-tijaar-quick-guide',
                'excerpt' => 'Ready to turn your products into sales? This guide walks you through signing up as a seller, creating your first listing, and getting your store noticed.',
                'content' => '<p>Becoming a seller on Tijaar is straightforward. Follow these steps to get your store up and running.</p><h2>Step 1: Create Your Account</h2><p>Sign up on Tijaar if you haven’t already. Complete your profile with a clear name and (optional) profile picture so buyers can trust you.</p><h2>Step 2: Open Your Seller Dashboard</h2><p>Apply to become a seller from your account. Once approved, you’ll get access to the seller dashboard where you can manage listings, orders, and payouts.</p><h2>Step 3: Add Your First Listing</h2><p>Upload clear photos, write an honest title and description, set your price, and choose shipping options. The more detail you give, the better buyers can decide.</p><h2>Step 4: Set Shipping & Returns</h2><p>Define how you ship orders and what your return policy is. Clear policies help avoid misunderstandings and build trust.</p><h2>Step 5: Respond to Orders & Messages</h2><p>When an order comes in, ship it on time and add tracking if possible. Reply to buyer messages quickly. Good communication leads to good reviews.</p><p>For more help, check our <a href="/help">Help Center</a> or <a href="/contact">Contact</a> support.</p>',
                'meta_title' => 'How to Start Selling on Tijaar: A Quick Guide',
                'meta_description' => 'A step-by-step guide to becoming a seller on Tijaar: sign up, create listings, set shipping, and grow your sales.',
                'is_published' => true,
                'published_at' => now()->subDays(8),
            ],
            [
                'title' => 'Why We Love Local Sellers: Spotlight on Tijaar Marketplace',
                'slug' => 'why-we-love-local-sellers-spotlight-tijaar',
                'excerpt' => 'Tijaar is built around local buyers and sellers. Here’s how our marketplace supports small businesses and helps you discover unique products close to home.',
                'content' => '<p>At Tijaar, we believe in the power of local commerce. When you buy from a seller in your region, you’re supporting real people and often getting faster, more personal service.</p><h2>Supporting Local Businesses</h2><p>Many of our sellers are small businesses and individuals. Your purchase helps them grow and keeps the marketplace diverse and full of choice.</p><h2>Faster Shipping & Lower Impact</h2><p>Local sellers often ship from nearby, which can mean quicker delivery and a smaller carbon footprint. Win-win for you and the environment.</p><h2>Unique Finds</h2><p>From handmade goods to regional products, local sellers bring items you might not find on big global platforms. Explore categories and discover something new.</p><h2>How You Can Get Started</h2><p>Browse by category, use search, or check out featured sellers on the homepage. Leave a review after your purchase to help other buyers and support great sellers.</p><p>Thank you for being part of the Tijaar community. Questions? Visit our <a href="/help">Help Center</a> or <a href="/contact">Contact</a> us.</p>',
                'meta_title' => 'Why We Love Local Sellers: Spotlight on Tijaar Marketplace',
                'meta_description' => 'How Tijaar supports local sellers and why buying local means faster shipping, unique products, and stronger communities.',
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Understanding Shipping & Delivery on Tijaar',
                'slug' => 'understanding-shipping-delivery-on-tijaar',
                'excerpt' => 'Who ships your order? How long does it take? This post answers common questions about shipping and delivery when you shop on Tijaar.',
                'content' => '<p>Shipping on Tijaar works a bit differently from a single online store. Here’s what you need to know.</p><h2>Who Ships My Order?</h2><p>Tijaar is a marketplace: sellers list products and ship them themselves (or use a courier). So each order may come from a different place. Shipping cost and speed are set by the seller and shown at checkout.</p><h2>How Long Will It Take?</h2><p>Delivery times vary by seller and location. You’ll usually see an estimate on the product page and in your order confirmation. Some sellers offer express shipping for an extra fee.</p><h2>Can I Track My Order?</h2><p>Yes. Once the seller ships your order, they can add a tracking number. You’ll see updates in your account under <strong>My Orders</strong>. If you don’t see tracking, you can message the seller.</p><h2>What If There’s a Problem?</h2><p>Contact the seller first from your order page. If you can’t resolve it, our support team can step in. For full details, see our <a href="/shipping">Shipping Info</a> and <a href="/returns-refunds">Returns & Refunds</a> pages.</p><p>Happy shopping! Need help? <a href="/contact">Contact us</a> anytime.</p>',
                'meta_title' => 'Understanding Shipping & Delivery on Tijaar',
                'meta_description' => 'How shipping works on Tijaar: who ships, delivery times, tracking, and what to do if something goes wrong.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'New on Tijaar: What’s Coming This Year',
                'slug' => 'new-on-tijaar-whats-coming-this-year',
                'excerpt' => 'We’re always improving Tijaar. Here’s a peek at what we’re working on—from a better search experience to more payment options and seller tools.',
                'content' => '<p>Thank you for being part of Tijaar. We’re committed to making the platform better for both buyers and sellers. Here’s a short look at what’s on the roadmap.</p><h2>Better Search & Discovery</h2><p>We’re improving search and filters so you can find exactly what you need faster. Sellers will get better tools to reach the right buyers.</p><h2>More Payment Options</h2><p>We’re adding more ways to pay so shopping is convenient for everyone. Stay tuned for updates in your region.</p><h2>Seller Tools & Analytics</h2><p>Sellers will get clearer insights into their sales and traffic. We’re also working on simpler listing and inventory management.</p><h2>Mobile Experience</h2><p>We want Tijaar to work great on every device. We’re polishing the mobile experience so you can browse and sell on the go.</p><h2>Your Feedback Matters</h2><p>Have ideas or ran into an issue? We’d love to hear from you. Use our <a href="/contact">Contact</a> page or <a href="/help">Help Center</a> to get in touch.</p><p>Thanks again for being part of our community. Here’s to a great year ahead!</p>',
                'meta_title' => 'New on Tijaar: What\'s Coming This Year',
                'meta_description' => 'A look at upcoming improvements on Tijaar: search, payments, seller tools, and mobile experience.',
                'is_published' => true,
                'published_at' => now()->subDay(1),
            ],
        ];

        foreach ($blogs as $data) {
            Blog::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'author_id' => $authorId,
                    'views_count' => 0,
                    'featured_image' => null,
                ])
            );
        }

        $this->command->info('Seeded 5 blogs.');
    }
}
