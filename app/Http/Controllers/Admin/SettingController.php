<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Support\UploadHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class SettingController extends Controller
{
    protected const WEB_FILE_KEYS = ['site_logo', 'favicon', 'login_logo', 'email_logo', 'email_banner', 'og_image'];

    public function index(): View
    {
        $groups = [
            'general' => [
                'label' => 'General',
                'keys' => ['site_name', 'site_tagline', 'support_email', 'support_phone', 'currency_default', 'timezone'],
            ],
            'web' => [
                'label' => 'Web / Branding',
                'keys' => [
                    'site_logo', 'favicon', 'login_logo', 'email_logo', 'email_banner', 'og_image',
                    'site_logo_alt', 'favicon_alt', 'login_logo_alt', 'email_logo_alt', 'email_banner_alt', 'og_image_alt',
                    'meta_title', 'meta_description', 'meta_keywords', 'meta_author',
                    'font_size_h1', 'font_size_h2', 'font_size_h3', 'font_size_h4', 'font_size_h5', 'font_size_h6',
                    'font_size_p', 'font_size_body',
                    'robots_txt', 'llm_txt',
                ],
            ],
            'topbar' => [
                'label' => 'Top Bar (Public Site)',
                'keys' => ['topbar_stat_1', 'topbar_stat_2', 'topbar_phone', 'topbar_facebook_url', 'topbar_twitter_url', 'topbar_instagram_url', 'topbar_youtube_url', 'topbar_music_url'],
            ],
            'contact_footer' => [
                'label' => 'Contact & Footer',
                'keys' => ['contact_phone', 'contact_email', 'contact_address', 'footer_tagline'],
            ],
            'product_settings' => [
                'label' => 'Product Settings',
                'keys' => [],
            ],
            'payout_settings' => [
                'label' => 'Seller Payout',
                'keys' => [],
            ],
            'locations' => [
                'label' => 'Locations',
                'keys' => [],
            ],
        ];

        \App\Services\LocationService::seedDefaults();

        $settings = Setting::pluck('value', 'key')->toArray();

        foreach ($groups as $group => $config) {
            foreach ($config['keys'] as $key) {
                if (!isset($settings[$key])) {
                    $settings[$key] = config('settings_defaults.' . $key, '');
                }
            }
        }

        return view('admin.settings.index', compact('groups', 'settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $groups = $request->input('settings', []);

        $secretKeys = ['mail_password'];

        foreach ($groups as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if (in_array($key, $secretKeys, true) && ($value === null || $value === '')) {
                continue;
            }
            if ($key === 'partial_payment_online_percent') {
                $value = (string) max(1, min(99, (int) $value));
            }
            // payout_hold_days presets: 0, 3, 7, 14, 30 (also allow any 0–90)
            if ($key === 'payout_hold_days') {
                $value = (string) max(0, min(90, (int) $value));
            }
            if (str_starts_with($key, 'font_size_') && is_string($value) && $value !== '') {
                $value = trim($value);
                if (!preg_match('/^\d+(\.\d+)?(rem|px|em|%)$/i', $value)) {
                    continue;
                }
            }
            Setting::set($key, $value ?? '');
        }

        foreach (self::WEB_FILE_KEYS as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                if ($file->isValid()) {
                    $old = Setting::get($fileKey);
                    if ($old) {
                        UploadHelper::deleteAny($old);
                    }
                    $path = UploadHelper::storePublic($file, 'settings');
                    Setting::set($fileKey, $path);
                }
            }
        }

        Cache::forget('settings');
        $this->syncConfigToRuntime();
        $this->syncMailConfigToRuntime();

        $validTabs = [
            'general', 'web', 'topbar', 'contact_footer', 'product_settings', 'payout_settings',
            'locations',
        ];
        $activeTab = $request->input('active_tab', 'general');
        if (! in_array($activeTab, $validTabs, true)) {
            $activeTab = 'general';
        }

        $changedKeys = array_keys(is_array($groups) ? $groups : []);
        ActivityLogger::log([
            'action_type' => 'settings_update',
            'target_table' => 'settings',
            'action_on' => null,
            'description' => 'General settings updated (tab: '.$activeTab.')'
                .($changedKeys ? ': '.implode(', ', array_slice($changedKeys, 0, 20)) : ''),
        ], $request);

        return back()
            ->with('success', 'Settings saved.')
            ->with('active_tab', $activeTab);
    }

    protected function syncMailConfigToRuntime(): void
    {
        $mailKeys = [
            'mail_mailer' => 'mail.default',
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_encryption' => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];
        foreach ($mailKeys as $settingKey => $configKey) {
            $v = Setting::get($settingKey);
            if ($settingKey === 'mail_encryption' && ($v === null || $v === '')) {
                Config::set($configKey, null);
                continue;
            }
            if ($v !== null && $v !== '') {
                Config::set($configKey, $v);
            }
        }
    }

    protected function syncConfigToRuntime(): void
    {
        $keys = [
            'stripe_key' => 'services.stripe.key',
            'stripe_secret' => 'services.stripe.secret',
            'stripe_webhook_secret' => 'services.stripe.webhook_secret',
            'stripe_pkr_to_usd' => 'services.stripe.pkr_to_usd',
            'paypal_client_id' => 'services.paypal.client_id',
            'paypal_client_secret' => 'services.paypal.secret',
            'paypal_mode' => 'services.paypal.mode',
            'jazzcash_merchant_id' => 'services.jazzcash.merchant_id',
            'jazzcash_password' => 'services.jazzcash.password',
            'jazzcash_integrity_salt' => 'services.jazzcash.integrity_salt',
            'jazzcash_checkout_url' => 'services.jazzcash.checkout_url',
            'jazzcash_mwallet_url' => 'services.jazzcash.mwallet_url',
            'jazzcash_mwallet_v2_url' => 'services.jazzcash.mwallet_v2_url',
            'jazzcash_status_inquiry_url' => 'services.jazzcash.status_inquiry_url',
            'jazzcash_return_url' => 'services.jazzcash.return_url',
            'leopards_api_key' => 'services.leopards.api_key',
            'leopards_api_password' => 'services.leopards.api_password',
            'easypaisa_store_id' => 'services.easypaisa.store_id',
            'easypaisa_hash_key' => 'services.easypaisa.hash_key',
            'easypaisa_checkout_url' => 'services.easypaisa.checkout_url',
            'easypaisa_postback_url' => 'services.easypaisa.postback_url',
        ];

        foreach ($keys as $settingKey => $configKey) {
            $v = Setting::get($settingKey);
            if ($v !== null && $v !== '') {
                Config::set($configKey, $v);
            }
        }
    }
}
