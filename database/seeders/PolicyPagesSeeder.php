<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seed Cookie Policy, Help Center, Returns & Refunds, and Shipping Info pages with sections.
 * Run: php artisan db:seed --class=PolicyPagesSeeder
 */
class PolicyPagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCookiePolicy();
        $this->seedHelpCenter();
        $this->seedReturnsRefunds();
        $this->seedShipping();
    }

    private function seedCookiePolicy(): void
    {
        $page = Page::where('slug', 'cookie-policy')->first();
        if (! $page) return;

        $sections = [
            ['title' => '1. What Are Cookies?', 'content' => '<p>Cookies are small text files stored on your device when you visit our website. They help us remember your preferences, keep you logged in, and improve how the site works.</p>'],
            ['title' => '2. How We Use Cookies', 'content' => '<p>We use cookies to:</p><ul><li><strong>Essential:</strong> Keep you signed in, manage your cart, and process checkout securely.</li><li><strong>Preferences:</strong> Remember your language, region, and display settings.</li><li><strong>Analytics:</strong> Understand how visitors use Tijaar so we can improve the site.</li><li><strong>Marketing (optional):</strong> With your consent, we may use cookies to show you relevant offers. You can turn this off in settings.</li></ul>'],
            ['title' => '3. Managing Cookies', 'content' => '<p>You can control or delete cookies through your browser settings. Blocking all cookies may affect your ability to log in, use the cart, or see personalized content. Essential cookies are needed for the site to function properly.</p>'],
            ['title' => '4. Third-Party Cookies', 'content' => '<p>We may use services from third parties (e.g. payment providers, analytics) that set their own cookies. Their use is governed by their privacy policies. We only work with partners that respect user privacy and applicable law.</p>'],
            ['title' => '5. Updates', 'content' => '<p>We may update this Cookie Policy from time to time. The latest version will always be on this page. Continued use of Tijaar after changes means you accept the updated policy.</p><p>For more information, see our <a href="/privacy">Privacy Policy</a> and <a href="/terms">Terms of Service</a>. If you have questions, <a href="/contact">contact us</a>.</p>'],
        ];

        $page->update([
            'banner_title' => 'Cookie Policy',
            'banner_subtitle' => 'How we use cookies on Tijaar to improve your experience.',
            'sections' => [
                'last_updated' => date('F j, Y'),
                'sections' => $sections,
                'footer_contact_text' => 'If you have questions about our use of cookies, please contact us.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);
        $this->command->info('Cookie Policy page seeded.');
    }

    private function seedHelpCenter(): void
    {
        $page = Page::where('slug', 'help')->first();
        if (! $page) return;

        $sections = [
            ['title' => '1. Getting Started', 'content' => '<p>Create an account, browse products, and place orders. Sellers can register and start listing after verification. Explore categories and use search to find what you need.</p>'],
            ['title' => '2. Orders & Tracking', 'content' => '<p>View your orders in <strong>My Account → Orders</strong>. Sellers may add a tracking number after shipping. For delivery issues, contact the seller first from your order page. You can track status in real time once the seller provides tracking details.</p>'],
            ['title' => '3. Returns & Refunds', 'content' => '<p>Each seller sets their return policy. Check the product page and seller profile. For damaged or wrong items, contact the seller or open a dispute. See our <a href="/returns-refunds">Returns & Refunds</a> page for full details.</p>'],
            ['title' => '4. Payments', 'content' => '<p>We accept multiple payment methods including card, wallet, and cash on delivery where available. Payment is processed securely. Refunds are issued according to our refund policy and the seller’s terms.</p>'],
            ['title' => '5. Seller Support', 'content' => '<p>If you sell on Tijaar, you can manage listings, orders, and payouts from your seller dashboard. For verification, payouts, or listing issues, visit the seller help section or contact support.</p>'],
            ['title' => '6. Need More Help?', 'content' => '<p>Visit our <a href="/faqs">FAQs</a>, <a href="/shipping">Shipping Info</a>, or <a href="/contact">Contact Us</a> page. Our support team is here to help with any questions about buying or selling on Tijaar.</p>'],
        ];

        $page->update([
            'banner_title' => 'Help Center',
            'banner_subtitle' => 'Find answers, guides, and support for buying and selling on Tijaar.',
            'sections' => [
                'last_updated' => date('F j, Y'),
                'sections' => $sections,
                'footer_contact_text' => 'Need more help? Contact our support team.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);
        $this->command->info('Help Center page seeded.');
    }

    private function seedReturnsRefunds(): void
    {
        $page = Page::where('slug', 'returns-refunds')->first();
        if (! $page) return;

        $sections = [
            ['title' => '1. Your Satisfaction Matters', 'content' => '<p>If you receive an item that is <strong>damaged or not as described</strong>, contact the seller through your order page. Most sellers will offer a replacement or refund. We are here to support a fair resolution.</p>'],
            ['title' => '2. Return Window', 'content' => '<p>The return window is shown on the product or seller profile. Returns must usually be requested within this period. Keep your order confirmation and any photos of the issue to speed up the process.</p>'],
            ['title' => '3. How to Request a Return', 'content' => '<ol><li>Go to <strong>My Account → Orders</strong> and open the order.</li><li>Click <strong>Request Return</strong> or <strong>Contact Seller</strong> and describe the issue.</li><li>If the seller approves, follow their return instructions (e.g. ship back to the address they provide).</li><li>Refunds are typically processed after the seller receives and confirms the return.</li></ol>'],
            ['title' => '4. Refund Timing', 'content' => '<p>Refunds are usually processed within a few business days after the return is confirmed. The time for the refund to appear in your account depends on your bank or payment method. Contact support if you do not see the refund within the expected timeframe.</p>'],
            ['title' => '5. Disputes', 'content' => '<p>If you cannot resolve an issue with the seller, you may open a dispute. Our team will review the case and help mediate. Please provide order details, photos, and communication with the seller when opening a dispute.</p>'],
            ['title' => '6. Exceptions', 'content' => '<p>Some items may be non-returnable (e.g. perishables, personalized items). Sellers must state this clearly on the listing. For help, see our <a href="/help">Help Center</a> or <a href="/contact">Contact Us</a>.</p>'],
        ];

        $page->update([
            'banner_title' => 'Returns & Refunds',
            'banner_subtitle' => 'Your satisfaction matters. Here is how returns and refunds work on Tijaar.',
            'sections' => [
                'last_updated' => date('F j, Y'),
                'sections' => $sections,
                'footer_contact_text' => 'Questions about returns or refunds? Contact us.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);
        $this->command->info('Returns & Refunds page seeded.');
    }

    private function seedShipping(): void
    {
        $page = Page::where('slug', 'shipping')->first();
        if (! $page) return;

        $sections = [
            ['title' => '1. Who Ships My Order?', 'content' => '<p>Tijaar is a <strong>marketplace</strong>. Sellers ship orders themselves (or use their chosen courier). You can see the seller’s shipping policy on their store profile and on the product page.</p>'],
            ['title' => '2. Shipping Cost & Options', 'content' => '<p>Shipping cost, speed, and coverage are set by each seller and shown on the product page and at checkout. Some sellers offer free shipping; others charge based on weight, size, or destination. Choose the option that works best for you.</p>'],
            ['title' => '3. Delivery Times', 'content' => '<p>Delivery times vary by seller and location—typically from a few days to 1–2 weeks. Some sellers offer express shipping for an extra fee. Estimated delivery is usually shown at checkout and in your order confirmation.</p>'],
            ['title' => '4. Tracking', 'content' => '<p>After your order is shipped, the seller may add a tracking number. View status in <strong>My Account → Orders</strong>. For delivery issues, contact the seller first; our support team can help if needed.</p>'],
            ['title' => '5. International Shipping', 'content' => '<p>Some sellers ship internationally. Check the product page and seller profile for available countries and any extra customs or import information. Delivery times may be longer for international orders.</p>'],
            ['title' => '6. More Help', 'content' => '<p>For more help, see our <a href="/help">Help Center</a> or <a href="/contact">Contact Us</a>. We are here to make your shopping experience smooth and reliable.</p>'],
        ];

        $page->update([
            'banner_title' => 'Shipping Information',
            'banner_subtitle' => 'How delivery works when you shop on Tijaar.',
            'sections' => [
                'last_updated' => date('F j, Y'),
                'sections' => $sections,
                'footer_contact_text' => 'Questions about shipping? Contact us.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);
        $this->command->info('Shipping Info page seeded.');
    }
}
