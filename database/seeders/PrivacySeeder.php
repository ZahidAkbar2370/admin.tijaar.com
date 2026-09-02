<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PrivacySeeder extends Seeder
{
    /**
     * Seed the Privacy Policy page with sections from the reference design.
     * Run after CmsSeeder. Run: php artisan db:seed --class=PrivacySeeder
     */
    public function run(): void
    {
        $page = Page::where('slug', 'privacy')->first();
        if (! $page) {
            $this->command->warn('Privacy page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Privacy Policy',
            'banner_subtitle' => 'Please read this privacy policy carefully to understand how we collect, use, and protect your personal data.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'Questions about the Privacy Policy? If you have any questions about this Privacy Policy, please contact us.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Privacy Policy page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        $data = [
            [
                'title' => '1. Introduction',
                'content' => '<p>At Tijaar, we are committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform. By using our services, you consent to the practices described in this policy. We encourage you to read this document carefully.</p>',
            ],
            [
                'title' => '2. Personal Information We Collect',
                'content' => '<p>We may collect the following types of personal information:</p><ul><li>Name</li><li>Email address</li><li>Phone number</li><li>Billing and shipping address</li><li>Payment information (processed securely by our payment providers)</li></ul><p>We collect this information when you register, place an order, or interact with our platform.</p>',
            ],
            [
                'title' => '3. How We Use It',
                'content' => '<p>We use your personal information to:</p><ul><li>Process and fulfill your orders</li><li>Improve our customer service and platform experience</li><li>Send you periodic emails (e.g. order updates, promotions, where you have opted in)</li><li>Personalize your experience and show relevant content</li><li>Comply with legal obligations and protect our rights</li></ul>',
            ],
            [
                'title' => '4. Ad Compliance Policy (Children)',
                'content' => '<p>Our platform is not directed at children under the age of 13 (or the applicable age in your jurisdiction). We do not knowingly collect personal information from children. If you are a parent or guardian and believe your child has provided us with personal data, please contact us and we will take steps to delete such information in accordance with applicable law.</p>',
            ],
            [
                'title' => '5. User Privacy & Cookies Policy',
                'content' => '<p>We use cookies and similar technologies to:</p><ul><li>Enhance site functionality and your browsing experience</li><li>Track site traffic and understand how visitors use our platform</li><li>Remember your preferences and settings</li></ul><p>You can manage or disable cookies through your browser settings. Note that some features may not work correctly if cookies are disabled.</p>',
            ],
            [
                'title' => '6. Prohibited & Restricted Items Policy',
                'content' => '<p>To maintain a safe and lawful marketplace, certain items cannot be sold or traded on Tijaar. This includes (but is not limited to) illegal goods, counterfeit items, hazardous materials, and products that violate intellectual property or local laws. We reserve the right to remove listings and take action against accounts that violate this policy.</p>',
            ],
            [
                'title' => '7. Returns & Disputes Policy',
                'content' => '<p>We provide guidelines for returns and dispute resolution between buyers and sellers. Buyers may be eligible for returns or refunds in accordance with seller policies and our platform rules. Disputes are handled through our resolution process. Please refer to the specific seller listing and our Help Center for detailed return and dispute procedures.</p>',
            ],
            [
                'title' => '8. Payments & Fees Policy',
                'content' => '<p>We accept various payment methods as displayed at checkout. Transaction fees may apply for certain services (e.g. selling or premium features). All payment processing is carried out through secure, compliant third-party providers. We do not store your full payment card details on our servers. Please review the payment and fees information at the point of transaction.</p>',
            ],
            [
                'title' => '9. Buyer Policy',
                'content' => '<p>As a buyer on Tijaar, you have the right to receive goods or services as described and to use our platform in accordance with our Terms of Service. You are responsible for providing accurate shipping and payment information and for communicating with sellers in good faith. Buyers must not abuse the returns or dispute process.</p>',
            ],
            [
                'title' => '10. Seller Policy',
                'content' => '<p>Sellers must list items accurately, fulfill orders in a timely manner, and comply with our marketplace rules. Sellers are responsible for the quality and legality of their listings and for resolving buyer issues. Listing requirements, fees, and performance standards are set out in our seller guidelines and may be updated from time to time.</p>',
            ],
            [
                'title' => '11. Marketplace Policy',
                'content' => '<p>Our marketplace is governed by rules that promote fairness, safety, and compliance. All users must conduct themselves in accordance with these rules and applicable law. We may suspend or terminate accounts that violate our marketplace policy. By using Tijaar, you agree to participate in the marketplace in good faith.</p>',
            ],
            [
                'title' => '12. Security',
                'content' => '<p>We implement technical and organizational measures to protect your personal data from unauthorized access, loss, or misuse. This includes encryption, secure connections, access controls, and regular reviews of our security practices. While we strive to protect your information, no method of transmission over the internet or electronic storage is 100% secure.</p>',
            ],
            [
                'title' => '13. Sharing of Information',
                'content' => '<p>We may share your information with third parties in the following circumstances: to process payments, to fulfill orders (e.g. shipping partners), to comply with legal requirements or lawful requests, or with your consent. We do not sell your personal data to third parties for their marketing purposes. Any sharing is subject to appropriate agreements to protect your data.</p>',
            ],
            [
                'title' => '14. Information We Collect',
                'content' => '<p>In addition to the personal information you provide, we may collect information automatically when you use our platform, such as device information, IP address, browser type, and usage data. We use this to improve our services, prevent fraud, and analyze trends. This information may be combined with the data you provide to us.</p>',
            ],
            [
                'title' => '15. How You Can Edit Information',
                'content' => '<p>You can update your personal profile information at any time by logging into your account and visiting your account or profile settings. You can change your name, email, phone number, and address. If you need help updating or deleting information, please contact our support team through the Contact page.</p>',
            ],
            [
                'title' => '16. Data Retention',
                'content' => '<p>We retain your personal data for as long as necessary to provide our services, comply with legal obligations, resolve disputes, and enforce our agreements. When data is no longer needed, we will delete or anonymize it in accordance with our retention policy and applicable law. You may request deletion of your data subject to certain conditions (see Your Data Protection Rights).</p>',
            ],
            [
                'title' => '17. Your Data Protection Rights – Access Data',
                'content' => '<p>You have the right to request copies of your personal data. We may charge a small fee for this service where permitted by law. To exercise this right, please contact us using the details on our Contact page.</p>',
            ],
            [
                'title' => '18. Right to Rectify',
                'content' => '<p>You have the right to request that we correct any information you believe is inaccurate. You also have the right to request that we complete any information you believe is incomplete. Contact us to submit a request for rectification.</p>',
            ],
            [
                'title' => '19. Right to Erasure',
                'content' => '<p>You have the right to request that we erase your personal data, under certain conditions (e.g. where the data is no longer necessary, you withdraw consent, or the data was processed unlawfully). We will consider your request in line with applicable law and our legal obligations. Contact us to submit a request for erasure.</p>',
            ],
        ];

        return $data;
    }
}
