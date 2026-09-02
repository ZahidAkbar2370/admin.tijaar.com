<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class TermsSeeder extends Seeder
{
    /**
     * Seed the Terms of Service page with 30 standard sections.
     * Run after CmsSeeder. Run: php artisan db:seed --class=TermsSeeder
     */
    public function run(): void
    {
        $page = Page::where('slug', 'terms')->first();
        if (! $page) {
            $this->command->warn('Terms page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Terms of Service',
            'banner_subtitle' => 'Please read these terms and conditions carefully before using our service.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'If you have any questions about these Terms, please contact us at',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Terms of Service page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        $titles = [
            '1. Introduction',
            '2. Acceptance of Terms',
            '3. User Accounts',
            '4. User Content',
            '5. Intellectual Property Rights',
            '6. Prohibited Activities',
            '7. Termination',
            '8. Disclaimer of Warranties',
            '9. Limitation of Liability',
            '10. Indemnification',
            '11. Governing Law',
            '12. Changes to Terms',
            '13. Privacy Policy',
            '14. Third-Party Links',
            '15. Dispute Resolution',
            '16. Class Action Waiver',
            '17. Export Control',
            '18. Feedback',
            '19. Entire Agreement',
            '20. Severability',
            '21. Waiver',
            '22. Assignment',
            '23. Force Majeure',
            '24. Notices',
            '25. Relationship of the Parties',
            '26. Survival',
            '27. Headings',
            '28. Language',
            '29. Contact Information',
            '30. User Conduct',
        ];

        $contents = [
            '<p>Welcome to Tijaar. By accessing or using our platform, you agree to be bound by these Terms of Service. Please read them carefully. We serve buyers and sellers in Pakistan and beyond.</p>',
            '<p>By creating an account, placing an order, or otherwise using our service, you accept and agree to be bound by these Terms and our Privacy Policy. If you do not agree, do not use the platform.</p>',
            '<p>You are responsible for maintaining the confidentiality of your account and for all activity under your account. You must provide accurate information when registering and notify us immediately of any unauthorized use.</p>',
            '<p>You retain ownership of content you submit. By submitting content, you grant us a non-exclusive, worldwide, royalty-free license to use, display, and distribute it in connection with the platform.</p>',
            '<p>All content on this site is owned by Tijaar or its licensors and is protected by copyright and other intellectual property laws. Tijaar and its logos are our trademarks. You may not use them without our written permission.</p>',
            '<p>You may not use Tijaar for illegal activity, fraud, counterfeit goods, harassment, or to harm others. We may remove content and suspend or terminate accounts that violate these terms.</p>',
            '<p>We may suspend or terminate your account at any time for breach of these terms. You may close your account at any time. Upon termination, your right to use the platform ceases.</p>',
            '<p>The platform is provided &quot;as is&quot; and &quot;as available&quot; without warranties of any kind. We disclaim all warranties including merchantability and fitness for a particular purpose.</p>',
            '<p>To the maximum extent permitted by law, Tijaar shall not be liable for any indirect, incidental, special, consequential, or punitive damages. Our total liability shall not exceed the amount you paid us in the twelve months before the claim arose.</p>',
            '<p>You agree to indemnify and hold harmless Tijaar and its affiliates from any claims, damages, or expenses arising from your use of the platform, your content, or your breach of these terms.</p>',
            '<p>These terms are governed by the laws of the jurisdiction in which Tijaar operates. Any disputes shall be resolved in the courts of that jurisdiction.</p>',
            '<p>We may update these terms from time to time. We will post the revised terms on this page and update the &quot;Last updated&quot; date. Continued use after changes means you accept the new terms.</p>',
            '<p>Your use of the platform is also governed by our <a href="/privacy">Privacy Policy</a>. By using Tijaar, you consent to our Privacy Policy.</p>',
            '<p>Our site may contain links to third-party websites. We are not responsible for their content or practices. Your use of third-party services is at your own risk.</p>',
            '<p>Any disputes shall be resolved through binding arbitration or in the courts specified in the Governing Law section, except where prohibited by law.</p>',
            '<p>You agree that any dispute resolution will be conducted only on an individual basis and not in a class, consolidated, or representative action.</p>',
            '<p>You may not use or export the platform in violation of applicable export laws. You represent that you are not located in a country subject to a government embargo.</p>',
            '<p>If you provide feedback or ideas about the platform, you grant us a perpetual, royalty-free license to use such feedback for any purpose.</p>',
            '<p>These terms, together with our Privacy Policy and any other policies referenced herein, constitute the entire agreement between you and Tijaar regarding the platform.</p>',
            '<p>If any provision of these terms is held invalid or unenforceable, the remaining provisions will remain in full force and effect.</p>',
            '<p>Our failure to enforce any right or provision shall not constitute a waiver. Any waiver must be in writing and signed by us.</p>',
            '<p>You may not assign or transfer these terms or your account without our prior written consent. We may assign our rights without restriction.</p>',
            '<p>We shall not be liable for any failure or delay resulting from circumstances beyond our reasonable control, including natural disasters, war, or government actions.</p>',
            '<p>We may provide notices by email, in-app message, or by posting on the platform. You may contact us via our <a href="/contact">Contact</a> page.</p>',
            '<p>Nothing in these terms shall create a partnership, joint venture, or agency relationship between you and Tijaar. You have no authority to bind Tijaar.</p>',
            '<p>Provisions that by their nature should survive termination (including indemnification and limitation of liability) shall survive.</p>',
            '<p>The section headings in these terms are for convenience only and have no legal or contractual effect.</p>',
            '<p>These terms are written in English. Any translation is for convenience only. The English version shall prevail in case of conflict.</p>',
            '<p>For questions about these Terms, please contact us through our <a href="/contact">Contact</a> page. We will respond as soon as reasonably possible.</p>',
            '<p>You agree to use the platform only for lawful purposes and in accordance with these terms. You must not use the platform in any way that could damage or impair the platform.</p>',
        ];

        $sections = [];
        foreach ($titles as $i => $title) {
            $sections[] = [
                'title' => $title,
                'content' => $contents[$i] ?? '<p>By using Tijaar, you agree to the terms set out in this section. We reserve the right to update these terms from time to time.</p>',
            ];
        }

        return $sections;
    }
}
