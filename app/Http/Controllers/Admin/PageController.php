<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomeSection;
use App\Models\Page;
use App\Support\HomeCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Page::orderBy('sort_order')->orderBy('title');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('title', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"));
        }

        $pages = $query->paginate(10)->withQueryString();
        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Page::create($data);
        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        if ($page->slug === 'home') {
            $heroSection = HomeSection::firstOrCreate(['key' => 'hero'], ['title' => 'Hero Section', 'config' => [], 'is_active' => true, 'sort_order' => 1]);
            $heroConfig = is_array($heroSection->config) ? $heroSection->config : [];
            $appSection = HomeSection::firstOrCreate(['key' => 'app_download'], ['title' => 'Get the App', 'config' => [], 'is_active' => true, 'sort_order' => 10]);
            $appConfig = is_array($appSection->config) ? $appSection->config : [];
            $newsletterSection = HomeSection::firstOrCreate(['key' => 'newsletter'], ['title' => 'Newsletter', 'config' => [], 'is_active' => true, 'sort_order' => 11]);
            $newsletterConfig = is_array($newsletterSection->config) ? $newsletterSection->config : [];
            $heroBanner = Banner::where('position', 'home_hero')->orderBy('sort_order')->first();
            return view('admin.pages.home-form', compact('page', 'heroConfig', 'heroBanner', 'appConfig', 'newsletterConfig'));
        }
        if ($page->slug === 'about') {
            $sections = $this->sectionsForAdminForm($page);
            return view('admin.pages.about-form', compact('page', 'sections'));
        }
        if ($page->slug === 'contact') {
            $sections = $this->sectionsForAdminForm($page);
            return view('admin.pages.contact-form', compact('page', 'sections'));
        }
        if ($page->slug === 'terms') {
            $sections = $this->sectionsForAdminForm($page);
            return view('admin.pages.terms-form', compact('page', 'sections'));
        }
        if ($page->slug === 'privacy') {
            $sections = $this->sectionsForAdminForm($page);
            return view('admin.pages.privacy-form', compact('page', 'sections'));
        }
        if (in_array($page->slug, ['cookie-policy', 'help', 'returns-refunds', 'shipping'], true)) {
            $sections = $this->sectionsForAdminForm($page);
            $formView = match ($page->slug) {
                'cookie-policy' => 'admin.pages.cookie-policy-form',
                'help' => 'admin.pages.help-form',
                'returns-refunds' => 'admin.pages.returns-refunds-form',
                'shipping' => 'admin.pages.shipping-form',
                default => 'admin.pages.form',
            };
            return view($formView, compact('page', 'sections'));
        }
        if ($page->slug === 'how-it-works') {
            $sections = $this->sectionsForAdminForm($page);
            return view('admin.pages.how-it-works-form', compact('page', 'sections'));
        }
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        if ($page->slug === 'home') {
            return $this->updateHomePage($request, $page);
        }
        if ($page->slug === 'about') {
            return $this->updateAboutPage($request, $page);
        }
        if ($page->slug === 'contact') {
            return $this->updateContactPage($request, $page);
        }
        if ($page->slug === 'terms') {
            return $this->updateTermsPage($request, $page);
        }
        if ($page->slug === 'privacy') {
            return $this->updatePrivacyPage($request, $page);
        }
        if ($page->slug === 'cookie-policy') {
            return $this->updateSectionBasedPage($request, $page, 'Cookie Policy page updated.');
        }
        if ($page->slug === 'help') {
            return $this->updateSectionBasedPage($request, $page, 'Help Center page updated.');
        }
        if ($page->slug === 'returns-refunds') {
            return $this->updateSectionBasedPage($request, $page, 'Returns & Refunds page updated.');
        }
        if ($page->slug === 'shipping') {
            return $this->updateSectionBasedPage($request, $page, 'Shipping Info page updated.');
        }
        if ($page->slug === 'how-it-works') {
            return $this->updateHowItWorksPage($request, $page);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $content = $data['content'] ?? $page->content;
        if (is_string($content)) {
            $content = self::normalizeEditorHeadings($content);
        }

        $page->update([
            'title' => $data['title'],
            'content' => $content,
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'] ?? $page->sort_order,
        ]);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    /**
     * Normalize Quill/editor HTML so headings display on frontend: convert p.ql-header-* to semantic h1/h2/h3.
     */
    public static function normalizeEditorHeadings(string $html): string
    {
        // Quill may output <p class="ql-header-1"> instead of <h1>; convert to semantic tags so frontend displays headings
        $replacements = [
            '/<p\s[^>]*class="[^"]*ql-header-1[^"]*"[^>]*>(.*?)<\/p>/is' => '<h1>$1</h1>',
            '/<p\s[^>]*class="[^"]*ql-header-2[^"]*"[^>]*>(.*?)<\/p>/is' => '<h2>$1</h2>',
            '/<p\s[^>]*class="[^"]*ql-header-3[^"]*"[^>]*>(.*?)<\/p>/is' => '<h3>$1</h3>',
        ];
        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }
        return $html;
    }

    /**
     * Update Home page: hero section config, hero banner image, and page meta.
     */
    protected function updateHomePage(Request $request, Page $page)
    {
        $request->validate([
            'hero_badge' => 'nullable|string|max:255',
            'hero_title' => 'nullable|string|max:255',
            'hero_title_line2' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:2000',
            'hero_cta_primary_text' => 'nullable|string|max:100',
            'hero_cta_primary_url' => 'nullable|string|max:500',
            'hero_cta_secondary_text' => 'nullable|string|max:100',
            'hero_cta_secondary_url' => 'nullable|string|max:500',
            'hero_feature1_title' => 'nullable|string|max:100',
            'hero_feature1_subtitle' => 'nullable|string|max:150',
            'hero_feature2_title' => 'nullable|string|max:100',
            'hero_feature2_subtitle' => 'nullable|string|max:150',
            'hero_feature3_title' => 'nullable|string|max:100',
            'hero_feature3_subtitle' => 'nullable|string|max:150',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'hero_image_alt' => 'nullable|string|max:255',
            'app_headline' => 'nullable|string|max:255',
            'app_highlight' => 'nullable|string|max:255',
            'app_description' => 'nullable|string|max:500',
            'app_rating_text' => 'nullable|string|max:150',
            'app_store_url' => 'nullable|string|max:500',
            'play_store_url' => 'nullable|string|max:500',
            'newsletter_heading' => 'nullable|string|max:255',
            'newsletter_subtitle' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $heroConfig = [
            'badge' => $request->input('hero_badge', ''),
            'title' => $request->input('hero_title', ''),
            'title_line2' => $request->input('hero_title_line2', ''),
            'subtitle' => $request->input('hero_subtitle', ''),
            'cta_primary_text' => $request->input('hero_cta_primary_text', ''),
            'cta_primary_url' => $request->input('hero_cta_primary_url', ''),
            'cta_secondary_text' => $request->input('hero_cta_secondary_text', ''),
            'cta_secondary_url' => $request->input('hero_cta_secondary_url', ''),
            'feature1_title' => $request->input('hero_feature1_title', ''),
            'feature1_subtitle' => $request->input('hero_feature1_subtitle', ''),
            'feature2_title' => $request->input('hero_feature2_title', ''),
            'feature2_subtitle' => $request->input('hero_feature2_subtitle', ''),
            'feature3_title' => $request->input('hero_feature3_title', ''),
            'feature3_subtitle' => $request->input('hero_feature3_subtitle', ''),
        ];

        HomeSection::updateOrCreate(
            ['key' => 'hero'],
            ['title' => 'Hero Section', 'config' => $heroConfig, 'is_active' => true, 'sort_order' => 1]
        );

        $appConfig = array_filter([
            'headline' => $request->input('app_headline'),
            'highlight' => $request->input('app_highlight'),
            'description' => $request->input('app_description'),
            'rating_text' => $request->input('app_rating_text'),
            'app_store_url' => $request->input('app_store_url'),
            'play_store_url' => $request->input('play_store_url'),
        ], fn ($v) => $v !== null && $v !== '');

        HomeSection::updateOrCreate(
            ['key' => 'app_download'],
            ['title' => 'Get the App', 'config' => $appConfig, 'is_active' => true, 'sort_order' => 10]
        );

        $newsletterConfig = array_filter([
            'heading' => $request->input('newsletter_heading'),
            'subtitle' => $request->input('newsletter_subtitle'),
        ], fn ($v) => $v !== null && $v !== '');

        HomeSection::updateOrCreate(
            ['key' => 'newsletter'],
            ['title' => 'Newsletter', 'config' => $newsletterConfig, 'is_active' => true, 'sort_order' => 11]
        );

        if ($request->hasFile('hero_image') && $request->file('hero_image')->isValid()) {
            $path = \App\Support\UploadHelper::storePublic($request->file('hero_image'), 'home');
            $banner = Banner::where('position', 'home_hero')->orderBy('sort_order')->first();
            if ($banner) {
                if ($banner->image_path) {
                    \App\Support\UploadHelper::deleteAny($banner->image_path);
                }
                $banner->update([
                    'image_path' => $path,
                    'title' => $request->input('hero_title') ?: 'Hero',
                    'image_alt' => $request->input('hero_image_alt'),
                ]);
            } else {
                Banner::create([
                    'title' => $request->input('hero_title') ?: 'Hero',
                    'position' => 'home_hero',
                    'image_path' => $path,
                    'image_alt' => $request->input('hero_image_alt'),
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        } else {
            $banner = Banner::where('position', 'home_hero')->orderBy('sort_order')->first();
            if ($banner) {
                $banner->update(['image_alt' => $request->input('hero_image_alt')]);
            }
        }

        $page->update([
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
        ]);

        HomeCache::clear();

        return redirect()->route('admin.pages.index')->with('success', 'Home page updated.');
    }

    /**
     * Update About Us page: banner + structured sections (mission, stats, values, journey, team, cta).
     */
    protected function updateAboutPage(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'mission_title' => 'nullable|string|max:255',
            'mission_description' => 'nullable|string|max:5000',
            'stats' => 'nullable|array',
            'stats.*.icon' => 'nullable|string|max:50',
            'stats.*.number' => 'nullable|string|max:20',
            'stats.*.label' => 'nullable|string|max:255',
            'values' => 'nullable|array',
            'values.*.title' => 'nullable|string|max:255',
            'values.*.description' => 'nullable|string|max:2000',
            'journey' => 'nullable|array',
            'journey.*.year' => 'nullable|string|max:20',
            'journey.*.title' => 'nullable|string|max:255',
            'journey.*.description' => 'nullable|string|max:1000',
            'team' => 'nullable|array',
            'team.*.name' => 'nullable|string|max:255',
            'team.*.role' => 'nullable|string|max:255',
            'cta_heading' => 'nullable|string|max:255',
            'cta_text' => 'nullable|string|max:1000',
            'cta_primary_text' => 'nullable|string|max:100',
            'cta_primary_url' => 'nullable|string|max:500',
            'cta_secondary_text' => 'nullable|string|max:100',
            'cta_secondary_url' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $stats = [];
        foreach ($request->input('stats', []) as $i => $s) {
            if (($s['number'] ?? '') !== '' || ($s['label'] ?? '') !== '') {
                $stats[] = [
                    'icon' => $s['icon'] ?? 'package',
                    'number' => $s['number'] ?? '',
                    'label' => $s['label'] ?? '',
                ];
            }
        }
        if (count($stats) === 0) {
            $stats = [
                ['icon' => 'package', 'number' => '100K+', 'label' => 'Active Products'],
                ['icon' => 'users', 'number' => '50K+', 'label' => 'Happy Customers'],
                ['icon' => 'store', 'number' => '2.5K+', 'label' => 'Verified Vendors'],
                ['icon' => 'cart', 'number' => '200K+', 'label' => 'Orders Delivered'],
            ];
        }

        $values = [];
        foreach ($request->input('values', []) as $v) {
            if (($v['title'] ?? '') !== '' || ($v['description'] ?? '') !== '') {
                $values[] = [
                    'title' => $v['title'] ?? '',
                    'description' => $v['description'] ?? '',
                ];
            }
        }

        $journey = [];
        foreach ($request->input('journey', []) as $j) {
            if (($j['year'] ?? '') !== '' || ($j['title'] ?? '') !== '') {
                $journey[] = [
                    'year' => $j['year'] ?? '',
                    'title' => $j['title'] ?? '',
                    'description' => $j['description'] ?? '',
                ];
            }
        }

        $team = [];
        $teamInput = $request->input('team', []);
        foreach ($teamInput as $i => $t) {
            $name = $t['name'] ?? '';
            $role = $t['role'] ?? '';
            $imagePath = null;
            $fileKey = 'team_' . $i . '_image';
            if ($request->hasFile($fileKey) && $request->file($fileKey)->isValid()) {
                $imagePath = \App\Support\UploadHelper::storePublic($request->file($fileKey), 'cms/team');
            }
            $existing = $page->sections['team'][$i] ?? [];
            if ($imagePath) {
                if (! empty($existing['image_path'])) {
                    \App\Support\UploadHelper::deleteAny($existing['image_path']);
                }
            } else {
                $imagePath = $existing['image_path'] ?? null;
            }
            if ($name !== '' || $role !== '' || $imagePath) {
                $team[] = [
                    'name' => $name,
                    'role' => $role,
                    'image_path' => $imagePath,
                    'alt' => $t['alt'] ?? ($existing['alt'] ?? ''),
                ];
            }
        }

        $sections = [
            'mission' => [
                'title' => $request->input('mission_title', 'Our Mission'),
                'description' => $request->input('mission_description', ''),
            ],
            'stats' => $stats,
            'values' => $values,
            'journey' => $journey,
            'team' => $team,
            'cta' => [
                'heading' => $request->input('cta_heading', 'Join Us on Our Journey'),
                'text' => $request->input('cta_text', ''),
                'primary_text' => $request->input('cta_primary_text', 'Become a Seller'),
                'primary_url' => $request->input('cta_primary_url', '/sellers'),
                'secondary_text' => $request->input('cta_secondary_text', 'Contact Us'),
                'secondary_url' => $request->input('cta_secondary_url', '/contact'),
            ],
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'About Us page updated.');
    }

    /**
     * Update Contact Us page: banner + structured sections (hero, contact_cards, map, form, support, social).
     */
    protected function updateContactPage(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'contact_cards' => 'nullable|array',
            'contact_cards.*.type' => 'nullable|string|in:phone,email,address',
            'contact_cards.*.label' => 'nullable|string|max:100',
            'contact_cards.*.value' => 'nullable|string|max:255',
            'contact_cards.*.subtext' => 'nullable|string|max:255',
            'map_heading' => 'nullable|string|max:255',
            'map_address' => 'nullable|string|max:500',
            'map_embed_url' => 'nullable|string|max:2000',
            'form_title' => 'nullable|string|max:255',
            'support_title' => 'nullable|string|max:255',
            'support_description' => 'nullable|string|max:2000',
            'support_phone_label' => 'nullable|string|max:100',
            'support_phone_value' => 'nullable|string|max:100',
            'support_email_label' => 'nullable|string|max:100',
            'support_email_value' => 'nullable|string|max:255',
            'social_title' => 'nullable|string|max:255',
            'social_subtext' => 'nullable|string|max:255',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'nullable|string|max:50',
            'social_links.*.url' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $cards = [];
        foreach ($request->input('contact_cards', []) as $c) {
            $type = $c['type'] ?? 'phone';
            if (! in_array($type, ['phone', 'email', 'address'], true)) {
                $type = 'phone';
            }
            $cards[] = [
                'type' => $type,
                'label' => $c['label'] ?? '',
                'value' => $c['value'] ?? '',
                'subtext' => $c['subtext'] ?? '',
            ];
        }
        if (count($cards) === 0) {
            $cards = [
                ['type' => 'phone', 'label' => 'Phone', 'value' => '+971 50 123 4567', 'subtext' => 'Call us anytime'],
                ['type' => 'email', 'label' => 'Email', 'value' => 'support@tijaar.com', 'subtext' => 'Send us an email'],
                ['type' => 'address', 'label' => 'Address', 'value' => 'Dubai, United Arab Emirates', 'subtext' => 'Visit our office'],
            ];
        }

        $socialLinks = [];
        foreach ($request->input('social_links', []) as $s) {
            $platform = $s['platform'] ?? '';
            $url = $s['url'] ?? '';
            if ($platform !== '' || $url !== '') {
                $socialLinks[] = ['platform' => $platform, 'url' => $url];
            }
        }

        $sections = [
            'hero' => [
                'title' => $request->input('banner_title', 'Get in Touch'),
                'subtitle' => $request->input('banner_subtitle', ''),
            ],
            'contact_cards' => $cards,
            'map' => [
                'heading' => $request->input('map_heading', 'Our Location'),
                'address' => $request->input('map_address', ''),
                'embed_url' => $request->input('map_embed_url', ''),
            ],
            'form_title' => $request->input('form_title', 'Send us a Message'),
            'support' => [
                'title' => $request->input('support_title', 'Need Immediate Help?'),
                'description' => $request->input('support_description', ''),
                'phone_label' => $request->input('support_phone_label', 'Call Us'),
                'phone_value' => $request->input('support_phone_value', ''),
                'email_label' => $request->input('support_email_label', 'Email Us'),
                'email_value' => $request->input('support_email_value', ''),
            ],
            'social' => [
                'title' => $request->input('social_title', 'Follow Us'),
                'subtext' => $request->input('social_subtext', 'Stay connected with us on social media'),
                'links' => $socialLinks,
            ],
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Contact Us page updated.');
    }

    /**
     * Update Terms of Service page: banner, last_updated, numbered sections (CRUD), footer.
     */
    protected function updateTermsPage(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:255',
            'last_updated' => 'nullable|string|max:100',
            'footer_contact_text' => 'nullable|string|max:1000',
            'footer_copyright' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $existing = $page->normalizedSections();
        $sections = [
            'last_updated' => $request->input('last_updated', ''),
            'sections' => $existing['sections'],
            'footer_contact_text' => $request->input('footer_contact_text', ''),
            'footer_copyright' => $request->input('footer_copyright', ''),
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Terms of Service page settings saved.');
    }

    /**
     * Update Privacy Policy page: same structure as Terms (banner, last_updated, sections, footer).
     */
    protected function updatePrivacyPage(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'last_updated' => 'nullable|string|max:100',
            'footer_contact_text' => 'nullable|string|max:1000',
            'footer_copyright' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $existing = $page->normalizedSections();
        $sections = [
            'last_updated' => $request->input('last_updated', ''),
            'sections' => $existing['sections'],
            'footer_contact_text' => $request->input('footer_contact_text', ''),
            'footer_copyright' => $request->input('footer_copyright', ''),
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Privacy Policy page settings saved.');
    }

    /**
     * Update a section-based CMS page (Cookie Policy, Help Center, Returns & Refunds, Shipping).
     * Same structure as Terms/Privacy: banner, last_updated, sections, footer.
     */
    protected function updateSectionBasedPage(Request $request, Page $page, string $successMessage): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'last_updated' => 'nullable|string|max:100',
            'footer_contact_text' => 'nullable|string|max:1000',
            'footer_copyright' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $existing = $page->normalizedSections();
        $sections = [
            'last_updated' => $request->input('last_updated', ''),
            'sections' => $existing['sections'],
            'footer_contact_text' => $request->input('footer_contact_text', ''),
            'footer_copyright' => $request->input('footer_copyright', ''),
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', $successMessage);
    }

    public function saveSection(Request $request, Page $page): \Illuminate\Http\RedirectResponse
    {
        abort_unless($page->isContentBlockPage(), 404);

        $validated = $request->validate([
            'section_index' => 'nullable|integer|min:-1',
            'section_title' => 'nullable|string|max:255',
            'section_content' => 'nullable|string|max:100000',
        ]);

        $title = trim((string) ($validated['section_title'] ?? ''));
        $content = self::normalizeEditorHeadings((string) ($validated['section_content'] ?? ''));

        if ($title === '' && trim(strip_tags($content)) === '') {
            return redirect()->route('admin.pages.edit', $page)
                ->withErrors(['section_title' => 'Enter a section title or content.'])
                ->withInput();
        }

        $payload = $page->normalizedSections();
        $list = $payload['sections'];
        $index = (int) ($validated['section_index'] ?? -1);
        $row = ['title' => $title, 'content' => $content];

        if ($index >= 0 && array_key_exists($index, $list)) {
            $list[$index] = $row;
            $message = 'Section updated and saved to database.';
        } else {
            $list[] = $row;
            $message = 'Section added and saved to database.';
        }

        $payload['sections'] = array_values($list);
        $page->update(['sections' => $payload]);

        return redirect()->route('admin.pages.edit', $page)->with('success', $message);
    }

    public function deleteSection(Request $request, Page $page, int $index): \Illuminate\Http\RedirectResponse
    {
        abort_unless($page->isContentBlockPage(), 404);

        $payload = $page->normalizedSections();
        $list = $payload['sections'];

        if (array_key_exists($index, $list)) {
            array_splice($list, $index, 1);
            $payload['sections'] = array_values($list);
            $page->update(['sections' => $payload]);
        }

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Section removed from database.');
    }

    public function moveSection(Request $request, Page $page, int $index, string $direction): \Illuminate\Http\RedirectResponse
    {
        abort_unless($page->isContentBlockPage(), 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $payload = $page->normalizedSections();
        $list = $payload['sections'];
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (! array_key_exists($index, $list) || ! array_key_exists($swapWith, $list)) {
            return redirect()->route('admin.pages.edit', $page);
        }

        $tmp = $list[$index];
        $list[$index] = $list[$swapWith];
        $list[$swapWith] = $tmp;
        $payload['sections'] = array_values($list);
        $page->update(['sections' => $payload]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Section order updated.');
    }

    /**
     * Update How It Works page: hero + buyer section (heading, subtitle, steps, CTA) + seller section + trust bar.
     */
    protected function updateHowItWorksPage(Request $request, Page $page): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_title' => 'nullable|string|max:255',
            'banner_subtitle' => 'nullable|string|max:500',
            'buyer_heading' => 'nullable|string|max:255',
            'buyer_subtitle' => 'nullable|string|max:500',
            'buyer_steps' => 'nullable|array',
            'buyer_steps.*.title' => 'nullable|string|max:255',
            'buyer_steps.*.description' => 'nullable|string|max:2000',
            'buyer_cta_text' => 'nullable|string|max:255',
            'buyer_cta_url' => 'nullable|string|max:500',
            'seller_heading' => 'nullable|string|max:255',
            'seller_subtitle' => 'nullable|string|max:500',
            'seller_steps' => 'nullable|array',
            'seller_steps.*.title' => 'nullable|string|max:255',
            'seller_steps.*.description' => 'nullable|string|max:2000',
            'seller_cta_text' => 'nullable|string|max:255',
            'seller_cta_url' => 'nullable|string|max:500',
            'trust_items' => 'nullable|array',
            'trust_items.*.label' => 'nullable|string|max:255',
            'trust_text' => 'nullable|string|max:2000',
            'trust_links' => 'nullable|array',
            'trust_links.*.text' => 'nullable|string|max:255',
            'trust_links.*.url' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $buyerSteps = [];
        foreach ($request->input('buyer_steps', []) as $s) {
            $title = $s['title'] ?? '';
            $description = $s['description'] ?? '';
            if ($title !== '' || $description !== '') {
                $buyerSteps[] = ['title' => $title, 'description' => $description];
            }
        }
        $sellerSteps = [];
        foreach ($request->input('seller_steps', []) as $s) {
            $title = $s['title'] ?? '';
            $description = $s['description'] ?? '';
            if ($title !== '' || $description !== '') {
                $sellerSteps[] = ['title' => $title, 'description' => $description];
            }
        }
        $trustItems = [];
        foreach ($request->input('trust_items', []) as $t) {
            $label = $t['label'] ?? '';
            if ($label !== '') {
                $trustItems[] = ['label' => $label];
            }
        }
        $trustLinks = [];
        foreach ($request->input('trust_links', []) as $l) {
            $text = $l['text'] ?? '';
            $url = $l['url'] ?? '';
            if ($text !== '' || $url !== '') {
                $trustLinks[] = ['text' => $text, 'url' => $url];
            }
        }

        $sections = [
            'buyer_heading' => $request->input('buyer_heading', ''),
            'buyer_subtitle' => $request->input('buyer_subtitle', ''),
            'buyer_steps' => $buyerSteps,
            'buyer_cta_text' => $request->input('buyer_cta_text', ''),
            'buyer_cta_url' => $request->input('buyer_cta_url', '/shop'),
            'seller_heading' => $request->input('seller_heading', ''),
            'seller_subtitle' => $request->input('seller_subtitle', ''),
            'seller_steps' => $sellerSteps,
            'seller_cta_text' => $request->input('seller_cta_text', ''),
            'seller_cta_url' => $request->input('seller_cta_url', '/sellers'),
            'trust_items' => $trustItems,
            'trust_text' => $request->input('trust_text', ''),
            'trust_links' => $trustLinks,
        ];

        $page->update([
            'title' => $request->input('title'),
            'banner_title' => $request->input('banner_title'),
            'banner_subtitle' => $request->input('banner_subtitle'),
            'sections' => $sections,
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'meta_keywords' => $request->input('meta_keywords'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'How It Works page updated.');
    }

    protected function sectionsForAdminForm(Page $page): array
    {
        if ($page->isStructuredPage()) {
            return $page->decodedSections();
        }

        $sections = $page->normalizedSections();
        $oldSections = old('sections');
        if (is_array($oldSections) && $oldSections !== []) {
            $sections['sections'] = $this->mapSectionRows($oldSections);
        }

        return $sections;
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    protected function mapSectionRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $content = (string) ($row['content'] ?? $row['description'] ?? $row['body'] ?? '');
            if ($title === '' && trim(strip_tags($content)) === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'content' => $content,
            ];
        }

        return array_values($out);
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    protected function resolveSectionsListFromRequest(Request $request, Page $page): array
    {
        if ($request->boolean('sections_submitted') || array_key_exists('sections', $request->all())) {
            $sections = $request->input('sections', []);
            if (! is_array($sections)) {
                return [];
            }

            return $this->normalizeSectionRows($this->mapSectionRows($sections));
        }

        if (is_string($request->input('sections_json')) && trim((string) $request->input('sections_json')) !== '') {
            $decoded = json_decode((string) $request->input('sections_json'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeSectionRows($this->mapSectionRows($decoded));
            }
        }

        return $this->resolveSectionsListFromLegacyInputs($request, $page);
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    protected function normalizeSectionRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'title' => $row['title'],
                'content' => self::normalizeEditorHeadings($row['content']),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    protected function resolveSectionsListFromLegacyInputs(Request $request, Page $page): array
    {
        $fallback = [];
        foreach ($request->input('sections', []) as $s) {
            if (! is_array($s)) {
                continue;
            }
            $title = trim((string) ($s['title'] ?? ''));
            $content = (string) ($s['content'] ?? '');
            if ($title === '' && trim(strip_tags($content)) === '') {
                continue;
            }
            $fallback[] = [
                'title' => $title,
                'content' => self::normalizeEditorHeadings($content),
            ];
        }

        return $fallback !== [] ? $fallback : $page->normalizedSections()['sections'];
    }

    /**
     * Upload image for rich text editor (insert image in page/blog content).
     * Supports TinyMCE/CKEditor 5 (JSON with location) and CKEditor 4 (callback script).
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required_without:upload|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'upload' => 'required_without:file|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);
        $file = $request->file('file') ?? $request->file('upload');
        if (!$file) {
            if ($request->has('CKEditorFuncNum')) {
                return response('<script>window.parent.CKEDITOR.tools.callFunction(' . (int) $request->CKEditorFuncNum . ', "", "No file");</script>');
            }
            return response()->json(['location' => ''], 422);
        }
        $path = \App\Support\UploadHelper::storePublic($file, 'cms');
        $url = \App\Support\UploadHelper::url($path);

        if ($request->has('CKEditorFuncNum')) {
            return response('<script>window.parent.CKEDITOR.tools.callFunction(' . (int) $request->CKEditorFuncNum . ', ' . json_encode($url) . ', "");</script>')->header('Content-Type', 'text/html; charset=UTF-8');
        }
        return response()->json(['location' => $url]);
    }
}
