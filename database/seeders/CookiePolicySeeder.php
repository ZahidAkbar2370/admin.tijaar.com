<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CookiePolicySeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::where('slug', 'cookie-policy')->first();
        if (! $page) {
            $this->command->warn('Cookie Policy page not found. Run CmsSeeder first.');
            return;
        }

        $sections = $this->getDefaultSections();
        $page->update([
            'banner_title' => 'Cookie Policy',
            'banner_subtitle' => 'How we use cookies and similar technologies to improve your experience on Tijaar.',
            'sections' => [
                'last_updated' => 'October 10, 2023',
                'sections' => $sections,
                'footer_contact_text' => 'If you have questions about our use of cookies, please contact us.',
                'footer_copyright' => '© 2024 Tijaar. All rights reserved.',
            ],
        ]);

        $this->command->info('Cookie Policy page seeded with ' . count($sections) . ' sections.');
    }

    private function getDefaultSections(): array
    {
        return [
            [
                'title' => '1. What Are Cookies?',
                'content' => '<p>Cookies are small text files stored on your device when you visit our website. They help us remember your preferences, keep you logged in, and improve how the site works. We may also use similar technologies such as local storage and pixels.</p>',
            ],
            [
                'title' => '2. How We Use Cookies',
                'content' => '<p>We use cookies to:</p><ul><li><strong>Essential:</strong> Keep you signed in, manage your cart, and process checkout securely. These are necessary for the site to function.</li><li><strong>Preferences:</strong> Remember your language, region, and display settings so you don\'t have to set them again.</li><li><strong>Analytics:</strong> Understand how visitors use Tijaar (e.g. which pages are most used) so we can improve the platform.</li><li><strong>Marketing (optional):</strong> With your consent, we may use cookies to show you relevant offers. You can turn this off in settings.</li></ul>',
            ],
            [
                'title' => '3. Managing Cookies',
                'content' => '<p>You can control or delete cookies through your browser settings. Most browsers let you block third-party cookies or all cookies. Blocking all cookies may affect your ability to log in, use the cart, or see personalized content. Essential cookies are needed for the site to function properly.</p>',
            ],
            [
                'title' => '4. Third-Party Cookies',
                'content' => '<p>We may use services from third parties (e.g. payment providers, analytics, advertising) that set their own cookies. Their use is governed by their privacy policies. We only work with partners that respect user privacy and applicable law. We do not control these third-party cookies.</p>',
            ],
            [
                'title' => '5. Updates',
                'content' => '<p>We may update this Cookie Policy from time to time to reflect changes in our practices or the law. The latest version will always be on this page with an updated &quot;Last updated&quot; date. Continued use of Tijaar after changes means you accept the updated policy. For more information, see our <a href="/privacy">Privacy Policy</a> and <a href="/terms">Terms of Service</a>.</p>',
            ],
        ];
    }
}
