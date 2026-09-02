<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::where('slug', 'shipping')->first();
        if (! $page) {
            $this->command->warn('Shipping Info page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Shipping Information',
            'banner_subtitle' => 'How delivery works when you shop on Tijaar. Sellers ship directly to you.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'For shipping or delivery questions, contact the seller from your order page or reach out to our support team.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Shipping Info page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        return [
            [
                'title' => '1. Who Ships My Order?',
                'content' => '<p>Tijaar is a marketplace. Sellers ship orders themselves (or use their chosen courier). When you buy, you are purchasing from the seller, and they are responsible for getting the item to you. Shipping cost, speed, and coverage are set by each seller and are shown on the product page and at checkout. We do not ship orders ourselves; we connect you with sellers who do.</p>',
            ],
            [
                'title' => '2. Shipping Costs and Options',
                'content' => '<p>Shipping costs vary by seller, item size and weight, and your location. Many sellers offer standard and express options. The exact cost is displayed before you confirm the order. Some sellers offer free shipping on orders over a certain amount. Check the product page and cart for the final shipping price. If you have multiple items from different sellers, each seller may charge shipping separately.</p>',
            ],
            [
                'title' => '3. Delivery Times',
                'content' => '<p>Delivery times vary by seller and destination. Typical delivery ranges from a few days to 1–2 weeks within the same region; international orders may take longer. Sellers usually specify an estimated dispatch time (e.g. &quot;Ships within 2–3 business days&quot;) on their listings. After dispatch, the courier\'s delivery time applies. You can see estimated delivery dates at checkout and in your order confirmation.</p>',
            ],
            [
                'title' => '4. Tracking Your Order',
                'content' => '<p>After the seller ships your order, they may add a tracking number to the order. You can view the status and tracking link under <strong>My Account → Orders</strong> by clicking on the order. If tracking isn\'t available yet, the seller may still be preparing the shipment. If a long time has passed with no update, contact the seller through the order page first; our support team can help if needed.</p>',
            ],
            [
                'title' => '5. Shipping Address',
                'content' => '<p>Make sure your shipping address is correct at checkout. You can save multiple addresses in your account and choose one when ordering. Sellers ship to the address you provide; we are not responsible for errors due to an wrong or incomplete address. If you need to change the address after placing an order, contact the seller immediately—they may be able to update it before shipping.</p>',
            ],
            [
                'title' => '6. International Shipping',
                'content' => '<p>Some sellers ship internationally. If you are ordering from another country, check the product page for &quot;Ships to&quot; and any extra customs or import information. Delivery times and costs may be higher. You may be responsible for customs duties or taxes in your country; these are typically not included in the price or shipping fee. Contact the seller if you have questions about international delivery.</p>',
            ],
            [
                'title' => '7. Damaged or Lost Shipments',
                'content' => '<p>If your order arrives damaged or is lost in transit, contact the seller from your order page with details and (for damage) photos. The seller may arrange a replacement or refund. If the issue is not resolved, you can open a dispute and our team will help. Keep packaging and any proof of delivery for reference.</p>',
            ],
            [
                'title' => '8. More Help',
                'content' => '<p>For more on orders and delivery, see our <a href="/help">Help Center</a>. For returns, see <a href="/returns-refunds">Returns &amp; Refunds</a>. To contact us, visit the <a href="/contact">Contact Us</a> page.</p>',
            ],
        ];
    }
}
