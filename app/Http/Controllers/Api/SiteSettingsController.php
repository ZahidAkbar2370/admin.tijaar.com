<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PartialPaymentService;
use App\Support\BrandLogo;
use App\Support\SeoTextHelper;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    /**
     * Public site settings for frontend: logos, favicon, SEO meta.
     * No auth required.
     */
    public function index(): JsonResponse
    {
        $appUrl = rtrim(config('app.url'), '/');

        $defaultLogoUrl = $appUrl . '/images/tijaar-logo.png';

        $url = function (?string $path) {
            if (!$path || $path === '') {
                return null;
            }
            return \App\Support\UploadHelper::url($path);
        };
        $logoUrl = function (?string $path) {
            if (!$path || $path === '') {
                return null;
            }
            return \App\Support\UploadHelper::deliveryUrl($path, 140, 85) ?? \App\Support\UploadHelper::url($path);
        };

        $siteName = Setting::get('site_name', config('app.name'));
        $siteTagline = Setting::get('site_tagline', '');
        $frontendUrl = rtrim((string) config('app.frontend_url', 'https://www.tijaar.com'), '/');
        $defaultMetaTitle = (string) config('settings_defaults.meta_title', '');
        $defaultMetaDescription = (string) config('settings_defaults.meta_description', '');
        $defaultMetaKeywords = (string) config('settings_defaults.meta_keywords', '');
        $defaultMetaAuthor = (string) config('settings_defaults.meta_author', 'tijaar.com');
        $metaTitle = SeoTextHelper::resolve((string) Setting::get('meta_title', ''), $defaultMetaTitle) ?: $siteName;
        $metaDescription = SeoTextHelper::resolve((string) Setting::get('meta_description', ''), $defaultMetaDescription);
        $metaKeywords = SeoTextHelper::resolve((string) Setting::get('meta_keywords', ''), $defaultMetaKeywords);
        $metaAuthor = SeoTextHelper::resolve((string) Setting::get('meta_author', ''), $defaultMetaAuthor);
        $seoPlaceholders = [
            'site_url' => $frontendUrl,
            'site_name' => $siteName,
            'meta_description' => $metaDescription,
        ];
        $defaultRobots = SeoTextHelper::defaultRobotsTxt($frontendUrl);
        $defaultLlm = SeoTextHelper::defaultLlmTxt($siteName, $frontendUrl, $metaDescription ?: $siteTagline);

        $codEnabled = (string) Setting::get('payment_cod_enabled', '1') !== '0';
        $stripeEnabled = (string) Setting::get('stripe_enabled', '0') === '1';
        $paypalEnabled = (string) Setting::get('paypal_enabled', '1') === '1';
        $jazzcashEnabled = (string) Setting::get('jazzcash_enabled', '0') === '1';
        $easypaisaEnabled = (string) Setting::get('easypaisa_enabled', '0') === '1';
        $partialEnabled = PartialPaymentService::isPartialEnabled();
        $partialPercent = PartialPaymentService::onlinePercent();

        $paymentMethods = [];
        if ($codEnabled) {
            $paymentMethods[] = [
                'value' => 'cod',
                'label' => 'Cash on Delivery (COD)',
                'desc' => 'Pay when you receive',
                'logo' => BrandLogo::paymentMethod('cod'),
            ];
        }
        if ($stripeEnabled) {
            $paymentMethods[] = [
                'value' => 'stripe',
                'label' => 'Credit/Debit Card',
                'desc' => '',
                'logo' => BrandLogo::paymentMethod('stripe'),
            ];
        }
        if ($paypalEnabled) {
            $paymentMethods[] = [
                'value' => 'paypal',
                'label' => 'PayPal',
                'desc' => 'Pay with PayPal',
                'logo' => BrandLogo::paymentMethod('paypal'),
            ];
        }
        if ($jazzcashEnabled) {
            $paymentMethods[] = [
                'value' => 'jazzcash',
                'label' => 'Jazzcash / Mobicash Account',
                'desc' => '',
                'logo' => BrandLogo::paymentMethod('jazzcash'),
            ];
            if ($partialEnabled) {
                $codPct = 100 - $partialPercent;
                $paymentMethods[] = [
                    'value' => 'jazzcash_partial',
                    'label' => sprintf('Pay %d%% online, %d%% on delivery', $partialPercent, $codPct),
                    'desc' => sprintf('%d%% via JazzCash now, %d%% cash on delivery', $partialPercent, $codPct),
                    'logo' => BrandLogo::paymentMethod('jazzcash'),
                ];
            }
        }
        if ($easypaisaEnabled) {
            $paymentMethods[] = [
                'value' => 'easypaisa',
                'label' => 'Easypaisa',
                'desc' => 'Pakistan mobile wallet',
                'logo' => BrandLogo::paymentMethod('easypaisa'),
            ];
        }
        $paymentMethods[] = [
            'value' => 'wallet',
            'label' => 'Tijaar Wallet',
            'desc' => 'Pay from your wallet balance',
        ];
        // Do not force COD when admin has disabled all gateways — checkout shows an empty state instead.

        $topbarStat1 = Setting::get('topbar_stat_1', '');
        $topbarStat2 = Setting::get('topbar_stat_2', '');
        $topbarStats = array_values(array_filter([$topbarStat1, $topbarStat2]));
        if (empty($topbarStats)) {
            $topbarStats = ['Verified Sellers', 'Secure Payments'];
        }

        return response()->json([
            'success' => true,
            'site_name' => $siteName,
            'site_tagline' => $siteTagline,
            'site_logo_url' => $logoUrl(Setting::get('site_logo')) ?: $defaultLogoUrl,
            'site_logo_alt' => Setting::get('site_logo_alt', ''),
            'favicon_url' => $url(Setting::get('favicon')),
            'favicon_alt' => Setting::get('favicon_alt', ''),
            'login_logo_url' => $logoUrl(Setting::get('login_logo')) ?: $logoUrl(Setting::get('site_logo')) ?: $defaultLogoUrl,
            'login_logo_alt' => Setting::get('login_logo_alt', ''),
            'email_logo_url' => $url(Setting::get('email_logo')),
            'email_logo_alt' => Setting::get('email_logo_alt', ''),
            'email_banner_url' => $url(Setting::get('email_banner')),
            'email_banner_alt' => Setting::get('email_banner_alt', ''),
            'og_image_url' => $url(Setting::get('og_image'))
                ?: $logoUrl(Setting::get('site_logo'))
                ?: $defaultLogoUrl,
            'og_image_alt' => Setting::get('og_image_alt', '') ?: Setting::get('site_logo_alt', 'Tijaar logo'),
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'meta_author' => $metaAuthor,
            'robots_txt' => SeoTextHelper::applyPlaceholders(
                SeoTextHelper::resolve((string) Setting::get('robots_txt', ''), $defaultRobots),
                $seoPlaceholders
            ),
            'llm_txt' => SeoTextHelper::applyPlaceholders(
                SeoTextHelper::resolve((string) Setting::get('llm_txt', ''), $defaultLlm),
                $seoPlaceholders
            ),
            'seo_h1' => $this->defaultSeoH1Templates(),
            'typography' => [
                'h1' => Setting::get('font_size_h1', config('settings_defaults.font_size_h1')),
                'h2' => Setting::get('font_size_h2', config('settings_defaults.font_size_h2')),
                'h3' => Setting::get('font_size_h3', config('settings_defaults.font_size_h3')),
                'h4' => Setting::get('font_size_h4', config('settings_defaults.font_size_h4')),
                'h5' => Setting::get('font_size_h5', config('settings_defaults.font_size_h5')),
                'h6' => Setting::get('font_size_h6', config('settings_defaults.font_size_h6')),
                'p' => Setting::get('font_size_p', config('settings_defaults.font_size_p')),
                'body' => Setting::get('font_size_body', config('settings_defaults.font_size_body')),
            ],
            'topbar_stats' => $topbarStats,
            'topbar_phone' => Setting::get('topbar_phone', ''),
            'topbar_social_links' => [
                'facebook' => Setting::get('topbar_facebook_url', '') ?: '#',
                'twitter' => Setting::get('topbar_twitter_url', '') ?: '#',
                'instagram' => Setting::get('topbar_instagram_url', '') ?: '#',
                'youtube' => Setting::get('topbar_youtube_url', '') ?: '#',
                'music' => Setting::get('topbar_music_url', '') ?: '#',
            ],
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'footer_tagline' => Setting::get('footer_tagline', ''),
            'payment_methods' => $paymentMethods,
            // JazzCash Wallet API v2.0 — mobile + CNIC on checkout.
            'jazzcash_checkout_mode' => 'mwallet_v2',
            'jazzcash_requires_mobile' => true,
            'jazzcash_requires_cnic' => true,
            'partial_payment_enabled' => $partialEnabled,
            'partial_payment_online_percent' => $partialPercent,
            'partial_payment_label' => sprintf('Pay %d%% online, %d%% on delivery', $partialPercent, 100 - $partialPercent),
            'leopards_courier_enabled' => (string) Setting::get('leopards_enabled', '0') === '1',
            'tcs_courier_enabled' => (string) Setting::get('tcs_enabled', '0') === '1',
            'postex_courier_enabled' => (string) Setting::get('postex_enabled', '0') === '1',
            'dex_courier_enabled' => (string) Setting::get('dex_enabled', '0') === '1',
            'daewoo_fastex_courier_enabled' => (string) Setting::get('daewoo_fastex_enabled', '0') === '1',
            'mnp_courier_enabled' => (string) Setting::get('mnp_enabled', '0') === '1',
            'baloch_cargo_courier_enabled' => (string) Setting::get('baloch_cargo_enabled', '0') === '1',
            'enabled_couriers' => \App\Support\CourierCatalog::enabled(),
            'deposit_methods' => array_values(array_filter([
                $stripeEnabled ? ['value' => 'stripe', 'label' => 'Card (Stripe)'] : null,
                $jazzcashEnabled ? ['value' => 'jazzcash', 'label' => 'JazzCash'] : null,
                $easypaisaEnabled ? ['value' => 'easypaisa', 'label' => 'Easypaisa'] : null,
            ])),
            'email_verification_required' => (string) Setting::get('email_verification_required', '1') === '1',
            'private_seller_must_verify_email' => (string) Setting::get('private_seller_must_verify_email', '0') === '1',
            'private_seller_must_verify_phone' => (string) Setting::get('private_seller_must_verify_phone', '0') === '1',
            'private_seller_must_verify_whatsapp' => (string) Setting::get('private_seller_must_verify_whatsapp', '0') === '1',
            ...\App\Services\RecaptchaService::publicConfig(),
        ]);
    }

    /** SEO H1 text templates — code defaults only (not admin-editable). */
    private function defaultSeoH1Templates(): array
    {
        $keys = [
            'home', 'category', 'subcategory', 'product', 'blog', 'blog_list',
            'policy', 'cms', 'shop', 'brand', 'seller_store',
            'search', 'search_empty', 'sellers', 'all_categories',
            'best_sellers', 'flash_deals', 'flash_deal', 'cart', 'checkout',
        ];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = config('settings_defaults.seo_h1_' . $key, '');
        }
        return $out;
    }
}
