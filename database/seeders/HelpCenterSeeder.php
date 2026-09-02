<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::where('slug', 'help')->first();
        if (! $page) {
            $this->command->warn('Help Center page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Help Center',
            'banner_subtitle' => 'Find answers, guides, and support for buying and selling on Tijaar.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'Still need help? Our support team is here for you.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Help Center page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        return [
            [
                'title' => '1. Getting Started',
                'content' => '<p>Welcome to Tijaar. To get started, create an account with your email and a secure password. You can browse as a guest, but you\'ll need an account to place orders or sell. Sellers can register and start listing after completing verification. Check your email for confirmation and follow the steps to verify your identity if you plan to sell.</p>',
            ],
            [
                'title' => '2. Browsing and Buying',
                'content' => '<p>Search for products using the search bar or browse by category. Each listing shows the seller, price, and shipping options. Add items to your cart and proceed to checkout when ready. You can save items to your wishlist for later. At checkout, enter your shipping address and choose a payment method. Once the order is placed, you\'ll receive a confirmation email.</p>',
            ],
            [
                'title' => '3. Orders and Tracking',
                'content' => '<p>View all your orders under <strong>My Account → Orders</strong>. Click on an order to see its status, tracking information (when the seller adds it), and delivery details. Sellers typically ship within 1–3 business days. If you don\'t see tracking yet, the seller may still be preparing the shipment. For delivery issues, contact the seller first from your order page; our support team can help if the issue isn\'t resolved.</p>',
            ],
            [
                'title' => '4. Returns and Refunds',
                'content' => '<p>Each seller sets their own return policy—check the product page and seller profile before buying. If you receive an item that is damaged or not as described, go to <strong>My Account → Orders</strong>, open the order, and use <strong>Request Return</strong> or <strong>Contact Seller</strong> to describe the issue. If the seller approves, follow their return instructions. Refunds are typically processed after the seller receives and confirms the return. For full details, see our <a href="/returns-refunds">Returns &amp; Refunds</a> page.</p>',
            ],
            [
                'title' => '5. Selling on Tijaar',
                'content' => '<p>To sell, register as a seller and complete the verification process. Once approved, you can create listings with photos, descriptions, and prices. Set your shipping options and return policy. When you receive an order, ship it within the time frame you specified and add tracking if possible. Respond to buyer messages promptly. Payouts are processed according to our seller terms. For more, visit the seller dashboard and our <a href="/sellers">Become a Seller</a> page.</p>',
            ],
            [
                'title' => '6. Payments and Security',
                'content' => '<p>We accept various payment methods at checkout (e.g. card, wallet, COD where available). Payment processing is handled by secure third-party providers. We do not store your full card details. If you notice an unauthorized charge, contact us and your payment provider immediately. Never share your password or one-time codes with anyone.</p>',
            ],
            [
                'title' => '7. Account and Profile',
                'content' => '<p>Update your profile, address, and notification preferences under <strong>My Account</strong>. You can change your password, email, and phone number from account settings. If you forget your password, use the &quot;Forgot password&quot; link on the login page. For account security issues or if you can\'t access your account, contact support.</p>',
            ],
            [
                'title' => '8. Contact Support',
                'content' => '<p>For questions not covered here, visit our <a href="/contact">Contact Us</a> page to send a message or find our contact details. You can also check the <a href="/faqs">FAQs</a> and <a href="/shipping">Shipping Info</a> pages. We aim to respond within 24–48 hours. Have your order number or account email ready when you reach out.</p>',
            ],
        ];
    }
}
