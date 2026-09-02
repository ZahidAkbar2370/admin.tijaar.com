<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => '<h1>Welcome to Tijaar</h1><p>Your trusted <strong>multi-seller marketplace</strong>. Browse products, connect with verified sellers, and shop with confidence across Pakistan and beyond.</p>',
                'banner_title' => null,
                'banner_subtitle' => null,
                'meta_title' => '',
                'meta_description' => '',
                'sort_order' => 0,
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => '<h1>About Tijaar</h1><p>Tijaar is the <strong>#1 multi-seller marketplace</strong> connecting buyers and sellers. We make buying and selling simple, secure, and enjoyable.</p><h2>Our Mission</h2><p>To build trust and access for everyone—whether you are shopping for the best deal or growing your business online.</p><h2>Our Values</h2><ul><li><strong>Trust</strong> – Verified sellers and secure payments</li><li><strong>Quality</strong> – High standards for listings and service</li><li><strong>Support</strong> – We are here to help buyers and sellers</li></ul><p>Learn more on our <a href="/contact">Contact</a> page or explore the <a href="/help">Help Center</a>.</p>',
                'banner_title' => 'About Us',
                'banner_subtitle' => 'Your trusted marketplace for Pakistan and beyond. We connect buyers with verified sellers.',
                'meta_title' => 'About Us',
                'meta_description' => 'Learn about Tijaar',
                'sort_order' => 1,
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => '<h1>Contact Us</h1><p>Get in touch. Use the contact form on this page or the details in the footer. We are here to help.</p>',
                'banner_title' => 'Get in Touch',
                'banner_subtitle' => 'Have a question? We\'re here to help. Use the form or the contact details below.',
                'meta_title' => 'Contact Us',
                'meta_description' => 'Contact Tijaar',
                'sort_order' => 2,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'content' => '<h1>Terms of Service</h1><p>Welcome to Tijaar. By using our platform, you agree to these terms. Please read them carefully.</p><h2>Using the Platform</h2><p>You may use Tijaar to <strong>browse, buy, or sell</strong> in line with our policies. You must be at least 18 years old and provide accurate information when registering.</p><h2>Buyers</h2><p>When you buy on Tijaar, you enter into a contract with the seller. Payment and delivery terms are between you and the seller, subject to our payment and dispute rules.</p><h2>Sellers</h2><p>Sellers must comply with our seller policies and applicable law. You are responsible for the accuracy of listings, shipping, and customer service.</p><h2>Contact</h2><p>For questions, see our <a href="/contact">Contact</a> page or <a href="/help">Help Center</a>. Also review our <a href="/privacy">Privacy Policy</a>.</p>',
                'banner_title' => 'Terms of Service',
                'banner_subtitle' => 'Please read our terms carefully. By using Tijaar you agree to these conditions.',
                'meta_title' => 'Terms',
                'meta_description' => 'Terms of Service',
                'sort_order' => 3,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'content' => '<h1>Privacy Policy</h1><p>Tijaar is committed to protecting your personal data. This policy explains how we collect, use, and protect your information.</p><h2>Information We Collect</h2><p>We collect information you provide (name, email, address, phone when you register, buy, or sell), and information from your use of the site (device, IP, browsing data).</p><h2>How We Use It</h2><p>We use your data to run the platform, process orders and payouts, improve our services, and send important notices. We do not sell your personal data to third parties for marketing.</p><h2>Your Rights</h2><p>You may have rights to access, correct, or delete your data. You can update your profile in your account and contact us for other requests.</p><p>For questions, <a href="/contact">contact us</a> or visit our <a href="/help">Help Center</a>. See also our <a href="/terms">Terms of Service</a> and <a href="/cookie-policy">Cookie Policy</a>.</p>',
                'banner_title' => 'Privacy Policy',
                'banner_subtitle' => 'How we collect, use, and protect your personal information.',
                'meta_title' => 'Privacy',
                'meta_description' => 'Privacy Policy',
                'sort_order' => 4,
            ],
            [
                'title' => 'Cookie Policy',
                'slug' => 'cookie-policy',
                'content' => '<h1>Cookie Policy</h1><p>We use <strong>cookies and similar technologies</strong> to improve your experience, remember your preferences, and understand how the site is used.</p><h2>What We Use</h2><p>Essential cookies are needed for the site to work. Analytics cookies help us improve the platform. You can manage your preferences in your browser settings.</p><p>By continuing to use Tijaar, you accept our use of cookies as described here. For more, see our <a href="/privacy">Privacy Policy</a> and <a href="/terms">Terms of Service</a>. If you have questions, <a href="/contact">contact us</a>.</p>',
                'banner_title' => 'Cookie Policy',
                'banner_subtitle' => 'How we use cookies to improve your experience.',
                'meta_title' => 'Cookie Policy',
                'meta_description' => 'Cookie Policy',
                'sort_order' => 5,
            ],
            [
                'title' => 'Help Center',
                'slug' => 'help',
                'content' => '<h1>Help Center</h1><p>Find answers to common questions about <strong>orders, shipping, returns</strong>, and your account.</p><h2>Getting Started</h2><p>Create an account, browse products, and place orders. Sellers can register and start listing after verification.</p><h2>Orders &amp; Tracking</h2><p>View your orders in <strong>My Account → Orders</strong>. Sellers may add a tracking number after shipping. For delivery issues, contact the seller first from your order page.</p><h2>Returns &amp; Refunds</h2><p>Each seller sets their return policy. Check the product page and seller profile. For damaged or wrong items, contact the seller or open a dispute. See our <a href="/returns-refunds">Returns &amp; Refunds</a> page for details.</p><h2>Need More Help?</h2><p>Visit <a href="/faqs">FAQs</a>, <a href="/shipping">Shipping Info</a>, or <a href="/contact">Contact Us</a>.</p>',
                'banner_title' => 'Help Center',
                'banner_subtitle' => 'Find answers, guides, and support for buying and selling on Tijaar.',
                'meta_title' => 'Help Center',
                'meta_description' => 'Get help with orders and account',
                'sort_order' => 6,
            ],
            [
                'title' => 'Shipping Info',
                'slug' => 'shipping',
                'content' => '<h1>Shipping Information</h1><p>Tijaar is a <strong>marketplace</strong>. Sellers ship orders themselves. Shipping cost, speed, and coverage are set by each seller and shown on the product page and at checkout.</p><h2>Who Ships My Order?</h2><p>Sellers ship orders (or use their chosen courier). You can see the seller’s shipping policy on their store profile.</p><h2>Delivery Times</h2><p>Delivery times vary by seller and location—typically from a few days to 1–2 weeks. Some sellers offer express shipping for an extra fee.</p><h2>Tracking</h2><p>After your order is shipped, the seller may add a tracking number. View status in <strong>My Account → Orders</strong>. For delivery issues, contact the seller first; our support team can help if needed.</p><p>For more help, see our <a href="/help">Help Center</a> or <a href="/contact">Contact Us</a>.</p>',
                'banner_title' => 'Shipping Information',
                'banner_subtitle' => 'How delivery works when you shop on Tijaar.',
                'meta_title' => 'Shipping Info',
                'meta_description' => 'Shipping and delivery information',
                'sort_order' => 7,
            ],
            [
                'title' => 'Returns & Refunds',
                'slug' => 'returns-refunds',
                'content' => '<h1>Returns &amp; Refunds</h1><p>Your satisfaction matters. If you receive an item that is <strong>damaged or not as described</strong>, contact the seller through your order page. Most sellers will offer a replacement or refund.</p><h2>Return Window</h2><p>The return window is shown on the product or seller profile. Returns must usually be requested within this period. Keep your order confirmation and any photos of the issue.</p><h2>How to Request a Return</h2><ol><li>Go to <strong>My Account → Orders</strong> and open the order.</li><li>Click <strong>Request Return</strong> or <strong>Contact Seller</strong> and describe the issue.</li><li>If the seller approves, follow their return instructions.</li><li>Refunds are typically processed after the seller receives and confirms the return.</li></ol><h2>Disputes</h2><p>If you cannot resolve an issue with the seller, you may open a dispute and our team will help mediate.</p><p>For help, see our <a href="/help">Help Center</a> or <a href="/contact">Contact Us</a>.</p>',
                'banner_title' => 'Returns & Refunds',
                'banner_subtitle' => 'Your satisfaction matters. Here is how returns and refunds work on Tijaar.',
                'meta_title' => 'Returns & Refunds',
                'meta_description' => 'Returns and refund policy',
                'sort_order' => 8,
            ],
            [
                'title' => 'Blog',
                'slug' => 'blog',
                'content' => '<h1>Blog</h1><p>Tips, news, and stories from the Tijaar team and our community. Stay updated on marketplace news and selling tips.</p>',
                'banner_title' => 'Blog',
                'banner_subtitle' => 'Tips, news, and stories from the Tijaar team and our community. Stay updated on marketplace news and selling tips.',
                'meta_title' => 'Blog – Tijaar',
                'meta_description' => 'Tips, news, and stories from Tijaar. Stay updated on marketplace news and selling tips.',
                'sort_order' => 9,
            ],
            [
                'title' => 'How It Works',
                'slug' => 'how-it-works',
                'content' => '<h1>How Tijaar Works</h1><p>Buy from trusted sellers or start selling yourself. Simple, secure, and built for Pakistan and beyond.</p>',
                'banner_title' => 'How Tijaar Works',
                'banner_subtitle' => 'Buy from trusted sellers or start selling yourself. Simple, secure, and built for Pakistan.',
                'meta_title' => 'How Tijaar Works – Tijaar',
                'meta_description' => 'Learn how Tijaar works for buyers and sellers: browse, buy, sell, and grow your business.',
                'sort_order' => 10,
            ],
        ];

        foreach ($pages as $p) {
            Page::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['is_active' => true])
            );
        }

        // Default sections for How It Works (so frontend can render from CMS)
        $howItWorks = Page::where('slug', 'how-it-works')->first();
        if ($howItWorks && empty($howItWorks->sections['buyer_heading'] ?? null)) {
            $howItWorks->update([
                'sections' => [
                    'buyer_heading' => 'For Buyers',
                    'buyer_subtitle' => 'Shop with confidence from verified sellers. Secure payments and buyer protection on every order.',
                    'buyer_steps' => [
                        ['title' => 'Browse & Search', 'description' => 'Explore products from verified sellers. Use categories, filters, and search to find what you need.'],
                        ['title' => 'Secure Checkout', 'description' => 'Add to cart and pay securely. We support multiple payment methods and protect your transactions.'],
                        ['title' => 'Fast Delivery', 'description' => 'Sellers ship directly. Track your order and get support from the seller or our team if needed.'],
                    ],
                    'buyer_cta_text' => 'Start Shopping',
                    'buyer_cta_url' => '/shop',
                    'seller_heading' => 'For Sellers',
                    'seller_subtitle' => 'Reach millions of buyers. List products, manage orders, and get paid. We handle the platform; you focus on selling.',
                    'seller_steps' => [
                        ['title' => 'Create Your Store', 'description' => 'Sign up as a seller, complete verification, and set up your store profile and policies.'],
                        ['title' => 'List Products', 'description' => 'Add products with images, descriptions, and variants. Set prices and manage inventory.'],
                        ['title' => 'Sell & Grow', 'description' => 'Receive orders, communicate with buyers, and get paid. Use promotions to boost visibility.'],
                    ],
                    'seller_cta_text' => 'Become a seller',
                    'seller_cta_url' => '/sellers',
                    'trust_items' => [
                        ['label' => 'Verified Sellers'],
                        ['label' => 'Secure Payments'],
                        ['label' => 'Reliable Shipping'],
                    ],
                    'trust_text' => 'Tijaar is the #1 multi-seller marketplace for Pakistan and beyond. We verify sellers, protect buyers, and make buying and selling simple and secure.',
                    'trust_links' => [
                        ['text' => 'FAQs', 'url' => '/faqs'],
                        ['text' => 'Contact Us', 'url' => '/contact'],
                    ],
                ],
            ]);
        }

        $faqs = [
            ['question' => 'How do I track my order?', 'answer' => 'You can track your order from your account dashboard under My Orders. Click on the order and you will see the tracking information.', 'category' => 'Orders', 'sort_order' => 1],
            ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Stripe, PayPal, JazzCash, Easypaisa, and Cash on Delivery (COD).', 'category' => 'Payments', 'sort_order' => 2],
            ['question' => 'How can I return an item?', 'answer' => 'Go to your order details and click "Open Dispute" to request a return. Our team will guide you through the process.', 'category' => 'Returns', 'sort_order' => 3],
        ];

        foreach ($faqs as $f) {
            Faq::firstOrCreate(['question' => $f['question']], array_merge($f, ['is_active' => true]));
        }

        $sections = [
            ['key' => 'hero', 'title' => 'Hero Section', 'config' => ['title' => 'Welcome to Tijaar', 'subtitle' => 'Your trusted marketplace'], 'sort_order' => 1],
            ['key' => 'featured_products', 'title' => 'Featured Products', 'config' => ['limit' => 12], 'sort_order' => 2],
            ['key' => 'deals', 'title' => 'Deals Section', 'config' => ['limit' => 8], 'sort_order' => 3],
            ['key' => 'app_download', 'title' => 'Get the App', 'config' => ['headline' => 'Get the App for a', 'highlight' => 'Better Experience', 'description' => 'Download our mobile app to browse listings on the go, get instant notifications.', 'rating_text' => '4.9 Rating • 100K+ Downloads'], 'sort_order' => 10],
            ['key' => 'newsletter', 'title' => 'Newsletter', 'config' => ['heading' => 'Never Miss a Deal!', 'subtitle' => 'Subscribe to our newsletter and be the first to know about new listings and exclusive deals.'], 'sort_order' => 11],
        ];

        foreach ($sections as $s) {
            HomeSection::firstOrCreate(['key' => $s['key']], array_merge($s, ['is_active' => true]));
        }

        if (Schema::hasTable('testimonials')) {
            $testimonials = [
                ['name' => 'Ahmed Al Maktoum', 'role' => 'Business Owner', 'company' => 'Dubai', 'content' => 'Sold my car within 3 days! The platform is incredibly easy to use. Highly recommended for anyone looking to buy or sell.', 'rating' => 5, 'sort_order' => 1],
                ['name' => 'Sarah Johnson', 'role' => 'Interior Designer', 'company' => 'Abu Dhabi', 'content' => 'Found beautiful furniture at great prices. The verified seller badges give me confidence when making purchases.', 'rating' => 5, 'sort_order' => 2],
                ['name' => 'Mohammed Hassan', 'role' => 'Tech Enthusiast', 'company' => 'Sharjah', 'content' => 'Best place to buy electronics. Got my MacBook Pro at an unbeatable price with fast, secure delivery.', 'rating' => 5, 'sort_order' => 3],
                ['name' => 'Fatima Khan', 'role' => 'Small Business Owner', 'company' => 'Lahore', 'content' => 'Tijaar helped me reach customers across Pakistan. Listing is simple and the support team is very responsive.', 'rating' => 5, 'sort_order' => 4],
            ];
            foreach ($testimonials as $t) {
                Testimonial::firstOrCreate(
                    ['name' => $t['name']],
                    array_merge($t, ['is_active' => true])
                );
            }
        }
    }
}
