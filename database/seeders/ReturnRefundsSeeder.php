<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class ReturnRefundsSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::where('slug', 'returns-refunds')->first();
        if (! $page) {
            $this->command->warn('Returns & Refunds page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Returns & Refunds',
            'banner_subtitle' => 'Your satisfaction matters. Here is how returns and refunds work on Tijaar.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'If you have questions about returns or refunds, please contact us or open a dispute from your order page.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Returns & Refunds page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        return [
            [
                'title' => '1. Overview',
                'content' => '<p>At Tijaar, we want you to shop with confidence. If you receive an item that is damaged, defective, or not as described, you can request a return or refund. Because Tijaar is a marketplace, each seller sets their own return window and conditions. Always check the product page and seller profile for the return policy before you buy.</p>',
            ],
            [
                'title' => '2. Return Window',
                'content' => '<p>The return window (e.g. 7, 14, or 30 days from delivery) is shown on the product listing or seller profile. Returns must usually be requested within this period. Keep your order confirmation and any photos or videos of the issue—they help speed up the process. Some items (e.g. perishables or personalized goods) may be non-returnable; this will be stated on the listing.</p>',
            ],
            [
                'title' => '3. How to Request a Return',
                'content' => '<p>To request a return:</p><ol><li>Go to My Account then Orders and open the order.</li><li>Click Request Return or Contact Seller and describe the issue (e.g. wrong item, damaged, not as described).</li><li>Upload photos if helpful. The seller will review and respond.</li><li>If the seller approves, follow their return instructions (e.g. ship the item back to the address they provide).</li><li>Once the seller receives and confirms the return, your refund will be processed according to their policy and our payment terms.</li></ol>',
            ],
            [
                'title' => '4. Refund Processing',
                'content' => '<p>Refunds are typically processed to the original payment method within 5–10 business days after the seller confirms the return. For card payments, your bank may take additional time to show the credit. If you paid by wallet, the amount will be credited back to your wallet. Partial refunds may be offered in some cases (e.g. if you keep the item but receive a discount).</p>',
            ],
            [
                'title' => '5. Disputes',
                'content' => '<p>If you and the seller cannot agree (e.g. the seller denies the return or you disagree with the outcome), you may open a dispute from your order page. Our team will review the case and may ask for more information or evidence. We will work to reach a fair resolution. Please communicate with the seller first and give them a chance to resolve the issue before escalating.</p>',
            ],
            [
                'title' => '6. Seller Responsibilities',
                'content' => '<p>Sellers are expected to describe items accurately, pack them properly, and honor their stated return policy. If a seller repeatedly fails to meet these standards, we may take action. Buyers can leave feedback after a transaction to help other shoppers and to hold sellers accountable.</p>',
            ],
            [
                'title' => '7. Need More Help?',
                'content' => '<p>For step-by-step guidance, visit our <a href="/help">Help Center</a>. To contact us directly, use the <a href="/contact">Contact Us</a> page. When reaching out, include your order number and a brief description of the issue so we can assist you quickly.</p>',
            ],
        ];
    }
}
