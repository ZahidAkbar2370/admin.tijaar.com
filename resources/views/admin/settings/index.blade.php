@extends('admin.layouts.app')

@section('admin-content')
<div class="w-full max-w-full">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Settings</h1>
    <p class="text-sm text-gray-500 mb-6">Configure site branding, products, payout, and locations. SMTP and email events are under <a href="{{ route('admin.email-settings.index') }}" class="text-primary hover:underline">Email Setting</a>.</p>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <form id="admin-settings-form" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" x-data="{ activeTab: {{ \Illuminate\Support\Js::from(old('active_tab', session('active_tab', 'general'))) }} }">
        @csrf
        <input type="hidden" name="active_tab" :value="activeTab">

        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            @foreach(['general' => 'General', 'web' => 'Web / Branding', 'topbar' => 'Top Bar', 'contact_footer' => 'Contact & Footer', 'product_settings' => 'Products', 'payout_settings' => 'Payout', 'locations' => 'Locations'] as $tab => $label)
                <button type="button" @click="activeTab = '{{ $tab }}'" :class="activeTab === '{{ $tab }}' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200'"
                        class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="space-y-6">
            @foreach($groups as $groupKey => $group)
                <div x-show="activeTab === '{{ $groupKey }}'" x-cloak class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $group['label'] }}</h2>
                    @if($groupKey === 'product_settings')
                        <p class="text-sm text-gray-600 mb-4">Seller product approval moved to <a href="{{ route('admin.seller-settings.index') }}" class="text-primary hover:underline">Customer as Seller</a>. Customer listing limits are under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline">Customer as Seller</a>.</p>
                    @elseif($groupKey === 'payout_settings')
                        <p class="text-sm text-gray-600 mb-4">Business seller payout hold moved to <a href="{{ route('admin.seller-settings.index') }}" class="text-primary hover:underline">Customer as Seller</a>. Customer payout hold is under <a href="{{ route('admin.people-settings.index', ['tab' => 'customer']) }}" class="text-primary hover:underline">Customer as Buyer</a>.</p>
                    @elseif($groupKey === 'locations')
                        <p class="text-sm text-gray-600 mb-4">Countries, provinces, and cities for checkout and seller addresses. Use <strong>Import from Leopards API</strong> on the Cities tab to sync courier city IDs.</p>
                    @endif
                    
                    <div class="space-y-4">
                        @if($groupKey === 'web')
                            @php
                                $appUrl = rtrim(config('app.url'), '/');
                                $webFileKeys = ['site_logo', 'favicon', 'login_logo', 'email_logo', 'email_banner', 'og_image'];
                                $webLabels = [
                                    'site_logo' => 'Site logo (header/shop)',
                                    'favicon' => 'Favicon (browser tab icon)',
                                    'login_logo' => 'Login page logo',
                                    'email_logo' => 'Email logo',
                                    'email_banner' => 'Email banner image',
                                    'og_image' => 'Open Graph image (social sharing)',
                                ];
                            @endphp
                            @foreach($webFileKeys as $key)
                                @php $val = $settings[$key] ?? ''; $imgUrl = $val ? \App\Support\UploadHelper::url($val) : null; @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $webLabels[$key] ?? $key }}</label>
                                    @if($imgUrl)
                                        <div class="mb-2 flex items-center gap-2">
                                            <img src="{{ $imgUrl }}" alt="" class="h-12 object-contain border border-gray-200 rounded-lg" onerror="this.style.display='none'">
                                            <span class="text-xs text-gray-500">Current</span>
                                        </div>
                                    @endif
                                    <input type="file" name="{{ $key }}" accept="image/*,.ico"
                                           class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    @include('admin.partials.image-alt-field', [
                                        'name' => 'settings['.$key.'_alt]',
                                        'value' => $settings[$key.'_alt'] ?? '',
                                    ])
                                </div>
                            @endforeach
                            @foreach(['meta_title', 'meta_description', 'meta_keywords', 'meta_author'] as $key)
                                @php
                                    $val = $settings[$key] ?? '';
                                    $label = match ($key) {
                                        'meta_title' => 'SEO Meta Title',
                                        'meta_description' => 'SEO Meta Description',
                                        'meta_keywords' => 'SEO Meta Keywords',
                                        'meta_author' => 'SEO Meta Author',
                                        default => $key,
                                    };
                                    $placeholder = config('settings_defaults.'.$key, '');
                                @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    @if($key === 'meta_description')
                                        <textarea name="settings[{{ $key }}]" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="{{ $placeholder }}">{{ old('settings.'.$key, $val) }}</textarea>
                                    @else
                                        <input type="text" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $val) }}"
                                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                               placeholder="{{ $placeholder }}">
                                    @endif
                                </div>
                            @endforeach
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900 mb-1">Typography — Font Sizes</h3>
                                <p class="text-sm text-gray-600 mb-4">Default font sizes for headings and body text in CMS pages, blogs, FAQs, and other rich-text content on the public site. Use CSS units such as <code class="text-xs bg-gray-100 px-1 rounded">rem</code> or <code class="text-xs bg-gray-100 px-1 rounded">px</code> (e.g. <code class="text-xs bg-gray-100 px-1 rounded">1.875rem</code> or <code class="text-xs bg-gray-100 px-1 rounded">30px</code>).</p>
                                @php
                                    $typographyFields = [
                                        'font_size_h1' => 'H1',
                                        'font_size_h2' => 'H2',
                                        'font_size_h3' => 'H3',
                                        'font_size_h4' => 'H4',
                                        'font_size_h5' => 'H5',
                                        'font_size_h6' => 'H6',
                                        'font_size_p' => 'Paragraph (p)',
                                        'font_size_body' => 'Body / rich-text base',
                                    ];
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($typographyFields as $key => $label)
                                        @php
                                            $val = $settings[$key] ?? config('settings_defaults.'.$key, '');
                                            $placeholder = config('settings_defaults.'.$key, '');
                                        @endphp
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                            <input type="text" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $val) }}"
                                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                   placeholder="{{ $placeholder }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @php
                                $frontendBase = rtrim(config('app.frontend_url', 'https://www.tijaar.com'), '/');
                            @endphp
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900 mb-1">robots.txt &amp; llm.txt</h3>
                                <p class="text-sm text-gray-600 mb-4">Crawler rules for search engines and AI bots on the public site. Leave blank to use smart defaults. Placeholders: <code class="text-xs bg-gray-100 px-1 rounded">{site_url}</code>, <code class="text-xs bg-gray-100 px-1 rounded">{site_name}</code>, <code class="text-xs bg-gray-100 px-1 rounded">{meta_description}</code></p>
                                <p class="text-sm text-gray-500 mb-4">Live URLs: <a href="{{ $frontendBase }}/robots.txt" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $frontendBase }}/robots.txt</a> · <a href="{{ $frontendBase }}/llm.txt" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $frontendBase }}/llm.txt</a> · <a href="{{ $frontendBase }}/sitemap.xml" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $frontendBase }}/sitemap.xml</a> · <a href="{{ route('admin.sitemap.index') }}" class="text-primary hover:underline">Manage sitemaps</a></p>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">robots.txt</label>
                                        <textarea name="settings[robots_txt]" rows="10" spellcheck="false"
                                                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                  placeholder="Leave empty for default (Allow /, block account/checkout paths, sitemap)">{{ old('settings.robots_txt', $settings['robots_txt'] ?? '') }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">llm.txt</label>
                                        <textarea name="settings[llm_txt]" rows="14" spellcheck="false"
                                                  class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                                  placeholder="Leave empty for default site summary for AI crawlers">{{ old('settings.llm_txt', $settings['llm_txt'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @elseif($groupKey === 'topbar')
                            <p class="text-sm text-gray-600 mb-4">Text and links shown in the blue top bar on the public site (stats, phone, social links). Leave blank to hide or use defaults.</p>
                            @php
                                $topbarLabels = [
                                    'topbar_stat_1' => 'Stat line 1 (e.g. Verified Sellers)',
                                    'topbar_stat_2' => 'Stat line 2 (e.g. Secure Payments)',
                                    'topbar_phone' => 'Contact phone (shown on mobile)',
                                    'topbar_facebook_url' => 'Facebook URL',
                                    'topbar_twitter_url' => 'Twitter / X URL',
                                    'topbar_instagram_url' => 'Instagram URL',
                                    'topbar_youtube_url' => 'YouTube URL',
                                    'topbar_music_url' => 'Music / TikTok URL',
                                ];
                            @endphp
                            @foreach($topbarLabels as $key => $label)
                                @php $val = $settings[$key] ?? ''; @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <input type="text" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $val) }}"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                           placeholder="{{ $key === 'topbar_phone' ? '+92 300 1234567' : 'https://' }}">
                                </div>
                            @endforeach
                        @elseif($groupKey === 'contact_footer')
                            <p class="text-sm text-gray-600 mb-4">Contact details shown on the Contact page and in the footer. Footer tagline appears under the logo.</p>
                            @php
                                $contactLabels = [
                                    'contact_phone' => 'Phone',
                                    'contact_email' => 'Email',
                                    'contact_address' => 'Address',
                                    'footer_tagline' => 'Footer tagline (short description under logo)',
                                ];
                            @endphp
                            @foreach($contactLabels as $key => $label)
                                @php $val = $settings[$key] ?? ''; @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    @if($key === 'footer_tagline')
                                        <textarea name="settings[{{ $key }}]" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="e.g. Tijaar is the #1 multi-seller marketplace...">{{ old('settings.'.$key, $val) }}</textarea>
                                    @else
                                        <input type="text" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $val) }}"
                                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                               placeholder="{{ $key === 'contact_phone' ? '+92 300 1234567' : ($key === 'contact_email' ? 'support@tijaar.com' : 'Pakistan') }}">
                                    @endif
                                </div>
                            @endforeach
                        @elseif($groupKey === 'locations')
                            @include('admin.settings.partials.locations')
                        @else
                        @foreach($group['keys'] as $key)
                            @php
                                $label = str_replace(['Jazzcash ', 'Easypaisa '], ['JazzCash ', 'Easypaisa '], str_replace('_', ' ', ucwords(str_replace('_', ' ', $key), ' ')));
                                $val = $settings[$key] ?? '';
                                $type = 'text';
                                $placeholder = '';
                                $helpText = '';
                                if (str_contains($key, 'password') || str_contains($key, 'secret') || str_contains($key, 'hash_key') || $key === 'leopards_api_password' || $key === 'tcs_bearer_token') { $type = 'password'; }
                                elseif (str_contains($key, 'enabled') || str_contains($key, '_required') || $key === 'private_listing_approval' || $key === 'payment_cod_enabled') { $type = 'checkbox'; }
                                elseif ($key === 'mail_port' || $key === 'partial_payment_online_percent' || $key === 'private_listing_limit' || $key === 'private_listing_free_limit' || $key === 'payout_hold_days') { $type = 'number'; }
                                elseif ($key === 'leopards_environment') { $type = 'select'; }
                                elseif ($key === 'courier_default_provider') { $type = 'select'; }
                                elseif ($key === 'tcs_environment') { $type = 'select'; }
                                elseif ($key === 'jazzcash_checkout_mode') { $type = 'select'; }
                                elseif (in_array($key, ['jazzcash_checkout_url', 'jazzcash_mwallet_url', 'jazzcash_mwallet_v2_url', 'jazzcash_status_inquiry_url', 'jazzcash_return_url', 'easypaisa_checkout_url', 'easypaisa_postback_url', 'leopards_api_url', 'tcs_api_url'])) {
                                    $placeholder = 'Leave blank for default';
                                }
                                if ($key === 'partial_payment_online_percent') {
                                    $label = 'Online payment percentage (1–99)';
                                    $helpText = 'Percentage of order total charged via JazzCash. Remainder is collected as cash on delivery. Example: 50 means half online, half COD.';
                                }
                                elseif ($key === 'partial_payment_enabled') {
                                    $label = 'Enable partial payment (JazzCash + COD)';
                                }
                                elseif ($key === 'payment_cod_enabled') {
                                    $label = 'Enable Cash on Delivery (COD)';
                                }
                                elseif ($key === 'stripe_enabled') {
                                    $label = 'Enable Stripe (card payments)';
                                }
                                elseif ($key === 'paypal_enabled') {
                                    $label = 'Enable PayPal';
                                }
                                elseif ($key === 'jazzcash_enabled') {
                                    $label = 'Enable JazzCash';
                                }
                                elseif ($key === 'easypaisa_enabled') {
                                    $label = 'Enable Easypaisa';
                                }
                                elseif ($key === 'email_verification_required') {
                                    $label = 'Require email verification after registration';
                                    $helpText = 'When enabled, customers must enter a 6-digit OTP from email before they can log in. When disabled, accounts are verified automatically on signup.';
                                }
                                elseif ($key === 'partial_payment_enabled') {
                                    $label = 'Enable partial payment (JazzCash + COD)';
                                }
                                elseif ($key === 'leopards_enabled') {
                                    $label = 'Enable Leopards Courier integration';
                                }
                                elseif ($key === 'courier_default_provider') {
                                    $label = 'Preferred Pakistan courier';
                                    $helpText = 'Pre-selected at checkout when both Leopards and TCS are enabled. Customers can still choose either enabled courier.';
                                }
                                elseif ($key === 'tcs_enabled') {
                                    $label = 'Enable TCS Courier integration';
                                }
                                elseif ($key === 'tcs_bearer_token') {
                                    $label = 'Middleware Bearer Token (from TCS)';
                                    $type = 'password';
                                    $helpText = 'JWT from TCS middleware package. Required for sandbox API calls.';
                                }
                                elseif ($key === 'tcs_username') {
                                    $label = 'TCS Username (ECOM)';
                                    $helpText = 'ECOM auth username from TCS (e.g. testenvio).';
                                }
                                elseif ($key === 'tcs_password') {
                                    $label = 'TCS Password (ECOM)';
                                    $type = 'password';
                                }
                                elseif ($key === 'tcs_client_id') {
                                    $label = 'TCS Client ID (optional note)';
                                    $helpText = 'From JWT clientid if TCS shared it (e.g. 215610059). Not required for sandbox ECOM auth.';
                                }
                                elseif ($key === 'tcs_client_secret') {
                                    $label = 'TCS Client Secret (optional)';
                                    $type = 'password';
                                    $helpText = 'Only if TCS later provides Authorization API clientsecret.';
                                }
                                elseif ($key === 'tcs_account') {
                                    $label = 'TCS Account (optional reference)';
                                    $helpText = 'Your TCS merchant account number. Kept for reference only — Tijaar does not book shipments.';
                                }
                                elseif ($key === 'tcs_api_url') {
                                    $label = 'TCS API base URL (optional)';
                                    $helpText = 'Leave blank for sandbox/production default from Environment.';
                                }
                                elseif ($key === 'leopards_api_key') {
                                    $label = 'Leopards API Key';
                                }
                                elseif ($key === 'leopards_api_password') {
                                    $label = 'Leopards API Password';
                                }
                                elseif ($key === 'leopards_api_url') {
                                    $label = 'Leopards API URL (optional)';
                                    $helpText = 'Leave blank to auto-pick URL from Environment. If you fill this, it must match Environment — or leave blank when switching to Production.';
                                }
                                elseif ($key === 'jazzcash_return_url') {
                                    $label = 'Return / callback URL';
                                    $helpText = 'Must be your Tijaar server callback (not JazzCash simulator). Recommended: ' . \App\Services\JazzCashService::recommendedReturnUrl() . ' — register the same URL in the JazzCash merchant portal so paid orders update automatically.';
                                }
                                elseif ($key === 'jazzcash_checkout_mode') {
                                    $label = 'JazzCash checkout mode';
                                    $helpText = 'portal = old hosted page redirect. mwallet_v1 / mwallet_v2 = REST APIs. Access denied on REST falls back to portal.';
                                }
                                elseif ($key === 'jazzcash_mwallet_url') {
                                    $label = 'MWallet API URL (v1.1)';
                                    $helpText = 'Default: ' . \App\Services\JazzCashService::DEFAULT_MWALLET_URL;
                                }
                                elseif ($key === 'jazzcash_mwallet_v2_url') {
                                    $label = 'MWallet API URL (v2.0)';
                                    $helpText = 'Default: ' . \App\Services\JazzCashService::DEFAULT_MWALLET_V2_URL . ' — needs mobile + CNIC.';
                                }
                                elseif ($key === 'jazzcash_checkout_url') {
                                    $label = 'Checkout URL (optional, legacy portal)';
                                    $helpText = 'Legacy hosted Payment Portal. Default: ' . \App\Services\JazzCashService::DEFAULT_CHECKOUT_URL;
                                }
                                elseif ($key === 'jazzcash_status_inquiry_url') {
                                    $label = 'Status Inquiry API URL (optional)';
                                    $helpText = 'Default: ' . \App\Services\JazzCashService::DEFAULT_STATUS_INQUIRY_URL . ' — used to reconcile pending payments.';
                                }
                                if ($key === 'email_verification_required') {
                                    $label = 'Require email verification after registration';
                                    $helpText = 'When enabled, customers must enter a 6-digit OTP from email before they can log in. When disabled, accounts are verified automatically on signup.';
                                }
                                if ($key === 'product_approval_required') {
                                    $label = 'Require approval for Seller products';
                                    $helpText = 'When enabled, all new products from sellers (stores) will need your approval before going live. When disabled, products are auto-published when sellers click Publish.';
                                }
                                elseif ($key === 'private_listing_approval') {
                                    $label = 'Require approval for Private Seller (Customer) listings';
                                    $helpText = 'When enabled, listings added by customers (private sellers) will need your approval before going live. When disabled, customer listings are published immediately.';
                                }
                                elseif ($key === 'private_listing_limit') {
                                    $label = 'Customer max products (with plan)';
                                    $type = 'number';
                                    $placeholder = '15';
                                    $helpText = 'Absolute maximum products a customer/private seller can manage (e.g. 10–15). Free tier is controlled by Free listing limit. Grant higher capacity via custom limit on the user page.';
                                }
                                elseif ($key === 'private_listing_free_limit') {
                                    $label = 'Customer free listing limit';
                                    $type = 'number';
                                    $placeholder = '3';
                                    $helpText = 'How many products a customer can add for free without a plan (default 3).';
                                }
                                elseif ($key === 'payout_hold_days') {
                                    $label = 'Payout holding period (days)';
                                    $type = 'number';
                                    $placeholder = '0';
                                    $helpText = 'Days after delivery before seller earnings are released to their available balance. 0 = release immediately. Run payouts:release-held daily to release after hold.';
                                }
                            @endphp
                            <div>
                                @if($type === 'checkbox')
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="hidden" name="settings[{{ $key }}]" value="0">
                                        <input type="checkbox" name="settings[{{ $key }}]" value="1" {{ $val ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5">
                                        <div>
                                            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                            @if($helpText)
                                                <p class="text-xs text-gray-500 mt-1">{{ $helpText }}</p>
                                            @endif
                                        </div>
                                    </label>
                                @elseif($type === 'select' && $key === 'leopards_environment')
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <select name="settings[{{ $key }}]" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            onchange="window.syncLeopardsApiUrl && window.syncLeopardsApiUrl(this.value)">
                                        <option value="staging" {{ old('settings.'.$key, $val) === 'staging' ? 'selected' : '' }}>Staging / Sandbox (often unavailable — 504)</option>
                                        <option value="production" {{ old('settings.'.$key, $val) === 'production' ? 'selected' : '' }}>Production (recommended)</option>
                                    </select>
                                @elseif($type === 'select' && $key === 'courier_default_provider')
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <select name="settings[{{ $key }}]" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="leopards" {{ old('settings.'.$key, $val) === 'leopards' ? 'selected' : '' }}>Leopards Courier</option>
                                        <option value="tcs" {{ old('settings.'.$key, $val) === 'tcs' ? 'selected' : '' }}>TCS Courier</option>
                                    </select>
                                @elseif($type === 'select' && $key === 'tcs_environment')
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <select name="settings[{{ $key }}]" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            onchange="window.syncTcsApiUrl && window.syncTcsApiUrl(this.value)">
                                        <option value="sandbox" {{ old('settings.'.$key, $val) === 'sandbox' ? 'selected' : '' }}>Sandbox (devconnect.tcscourier.com)</option>
                                        <option value="production" {{ old('settings.'.$key, $val) === 'production' ? 'selected' : '' }}>Production (ociconnect.tcscourier.com)</option>
                                    </select>
                                @elseif($type === 'select' && $key === 'jazzcash_checkout_mode')
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <select name="settings[{{ $key }}]" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="mwallet_v2" {{ old('settings.'.$key, $val ?: 'mwallet_v2') === 'mwallet_v2' ? 'selected' : '' }}>MWallet REST v2.0 (mobile + CNIC — JazzCash required)</option>
                                        <option value="mwallet_v1" {{ old('settings.'.$key, $val) === 'mwallet_v1' ? 'selected' : '' }}>MWallet REST v1.1 (mobile only)</option>
                                        <option value="portal" {{ old('settings.'.$key, $val) === 'portal' ? 'selected' : '' }}>Payment Portal only (legacy)</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Orders always use MWallet v2.0 (JazzCash guidance). Checkout collects JazzCash mobile + CNIC. No portal redirect.</p>
                                @else
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                    <input type="{{ $type }}" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $val) }}"
                                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                           placeholder="{{ $placeholder }}"
                                           @if($type === 'number' && $key === 'payout_hold_days') min="0" @endif
                                           @if($type === 'number' && $key === 'partial_payment_online_percent') min="1" max="99" @endif
                                           @if($type === 'password') autocomplete="new-password" @endif>
                                @endif
                                @if(!empty($helpText) && $type !== 'checkbox')
                                    <p class="text-xs text-gray-500 mt-1">{{ $helpText }}</p>
                                @endif
                            </div>
                        @endforeach
                        @endif
                    </div>

                    @if($groupKey !== 'locations')
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-medium hover:bg-primary-dark transition cursor-pointer">
                                Save {{ $group['label'] }} Settings
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </form>
</div>

<script>
window.syncLeopardsApiUrl = function (environment) {
    const urlInput = document.getElementsByName('settings[leopards_api_url]')[0];
    if (!urlInput) return;
    urlInput.value = environment === 'production'
        ? 'https://merchantapi.leopardscourier.com/api'
        : 'https://merchantapistaging.leopardscourier.com/api';
};

window.syncTcsApiUrl = function (environment) {
    const urlInput = document.getElementsByName('settings[tcs_api_url]')[0];
    if (!urlInput) return;
    urlInput.value = environment === 'production'
        ? 'https://ociconnect.tcscourier.com'
        : 'https://devconnect.tcscourier.com';
};

document.addEventListener('alpine:init', () => {
    Alpine.data('leopardsAdminPanel', (testUrl, citiesUrl, csrfToken) => ({
        message: '',
        isSuccess: false,
        loading: false,
        citiesLoading: false,
        showCities: false,
        cities: [],
        citiesMeta: {},
        citySearch: '',
        fieldValue(name) {
            const el = document.getElementsByName(name)[0];
            return el ? el.value : '';
        },
        credentialPayload() {
            return {
                api_key: this.fieldValue('settings[leopards_api_key]'),
                api_password: this.fieldValue('settings[leopards_api_password]'),
                api_url: this.fieldValue('settings[leopards_api_url]'),
                environment: this.fieldValue('settings[leopards_environment]') || 'staging',
            };
        },
        testConnection() {
            this.loading = true;
            this.message = '';
            fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(this.credentialPayload()),
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.loading = false;
                    this.isSuccess = ok && data.success === true;
                    this.message = data.message || (data.success ? 'Connected.' : 'Connection failed.');
                })
                .catch(() => {
                    this.loading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed. Check your connection and try again.';
                });
        },
        loadCities(refresh) {
            this.citiesLoading = true;
            this.message = refresh
                ? 'Refreshing the Leopards city list…'
                : 'Loading Leopards city IDs for cities in Settings → Locations…';
            this.isSuccess = true;
            const payload = {
                ...this.credentialPayload(),
                refresh: !!refresh,
            };
            fetch(citiesUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.citiesLoading = false;
                    if (ok && data.success) {
                        this.cities = data.cities || [];
                        this.citiesMeta = {
                            total_managed: data.total_managed,
                            linked_leopards_ids: data.linked_leopards_ids,
                        };
                        this.showCities = true;
                        this.isSuccess = true;
                        const linked = this.cities.filter((c) => c.linked).length;
                        this.message = this.cities.length + ' managed cities — '
                            + linked + ' linked to a Leopards city ID, '
                            + (this.cities.length - linked) + ' not linked.';
                    } else {
                        this.isSuccess = false;
                        this.message = data.message || 'Could not load cities.';
                    }
                })
                .catch(() => {
                    this.citiesLoading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed or timed out. Try again or use Refresh rates.';
                });
        },
        filteredCities() {
            const q = (this.citySearch || '').trim().toLowerCase();
            if (!q) {
                return this.cities;
            }
            return this.cities.filter((c) => (c.city_name || '').toLowerCase().includes(q));
        },
    }));

    Alpine.data('leopardsTestConnection', (testUrl, csrfToken) => ({
        message: '',
        isSuccess: false,
        loading: false,
        fieldValue(name) {
            const el = document.getElementsByName(name)[0];
            return el ? el.value : '';
        },
        testConnection() {
            this.loading = true;
            this.message = '';
            const payload = {
                api_key: this.fieldValue('settings[leopards_api_key]'),
                api_password: this.fieldValue('settings[leopards_api_password]'),
                api_url: this.fieldValue('settings[leopards_api_url]'),
                environment: this.fieldValue('settings[leopards_environment]') || 'staging',
            };
            fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.loading = false;
                    this.isSuccess = ok && data.success === true;
                    this.message = data.message || (data.success ? 'Connected.' : 'Connection failed.');
                })
                .catch(() => {
                    this.loading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed. Check your connection and try again.';
                });
        },
    }));

    Alpine.data('tcsTestConnection', (testUrl, csrfToken) => ({
        message: '',
        isSuccess: false,
        loading: false,
        fieldValue(name) {
            const el = document.getElementsByName(name)[0];
            return el ? el.value : '';
        },
        testConnection() {
            this.loading = true;
            this.message = '';
            const payload = {
                bearer_token: this.fieldValue('settings[tcs_bearer_token]'),
                username: this.fieldValue('settings[tcs_username]'),
                password: this.fieldValue('settings[tcs_password]'),
                client_id: this.fieldValue('settings[tcs_client_id]'),
                client_secret: this.fieldValue('settings[tcs_client_secret]'),
                tcs_account: this.fieldValue('settings[tcs_account]'),
                api_url: this.fieldValue('settings[tcs_api_url]'),
                environment: this.fieldValue('settings[tcs_environment]') || 'sandbox',
            };
            fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.loading = false;
                    this.isSuccess = ok && data.success === true;
                    this.message = data.message || (data.success ? 'Connected.' : 'Connection failed.');
                })
                .catch(() => {
                    this.loading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed. Check your connection and try again.';
                });
        },
    }));

    Alpine.data('jazzcashTestConnection', (testUrl, csrfToken) => ({
        message: '',
        isSuccess: false,
        loading: false,
        fieldValue(name) {
            const el = document.getElementsByName(name)[0];
            return el ? el.value : '';
        },
        testConnection() {
            this.loading = true;
            this.message = '';
            const payload = {
                merchant_id: this.fieldValue('settings[jazzcash_merchant_id]'),
                password: this.fieldValue('settings[jazzcash_password]'),
                integrity_salt: this.fieldValue('settings[jazzcash_integrity_salt]'),
                checkout_url: this.fieldValue('settings[jazzcash_checkout_url]'),
                return_url: this.fieldValue('settings[jazzcash_return_url]'),
            };
            fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.loading = false;
                    this.isSuccess = ok && data.success === true;
                    this.message = data.message || (data.success ? 'Configuration OK.' : 'Test failed.');
                })
                .catch(() => {
                    this.loading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed. Check your connection and try again.';
                });
        },
    }));

    Alpine.data('locationsAdminPanel', (cfg) => ({
        section: 'countries',
        message: '',
        isSuccess: false,
        importing: false,
        countries: [],
        provinces: [],
        cities: [],
        importProvinceId: '',
        countryForm: { id: null, name: '', code: '', sort_order: 0, is_active: true },
        provinceForm: { id: null, country_id: '', name: '', sort_order: 0, is_active: true },
        cityForm: { id: null, province_id: '', name: '', leopards_city_id: '', sort_order: 0, is_active: true },
        headers(json = true) {
            const h = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': cfg.csrf,
            };
            if (json) h['Content-Type'] = 'application/json';
            return h;
        },
        flash(msg, ok = true) {
            this.message = msg;
            this.isSuccess = ok;
        },
        async init() {
            await this.reloadAll();
        },
        async reloadAll() {
            await Promise.all([this.loadCountries(), this.loadProvinces(), this.loadCities()]);
        },
        async loadCountries() {
            const r = await fetch(cfg.countriesUrl, { headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (data.success) this.countries = data.countries || [];
        },
        async loadProvinces() {
            const r = await fetch(cfg.provincesUrl, { headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (data.success) this.provinces = data.provinces || [];
        },
        async loadCities() {
            const r = await fetch(cfg.citiesUrl, { headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (data.success) this.cities = data.cities || [];
        },
        resetCountryForm() {
            this.countryForm = { id: null, name: '', code: '', sort_order: 0, is_active: true };
        },
        editCountry(row) {
            this.countryForm = { id: row.id, name: row.name, code: row.code || '', sort_order: row.sort_order || 0, is_active: row.is_active };
            this.section = 'countries';
        },
        async saveCountry() {
            if (!(this.countryForm.name || '').trim()) {
                return this.flash('Country name is required.', false);
            }
            const body = { name: this.countryForm.name, code: this.countryForm.code, sort_order: this.countryForm.sort_order, is_active: this.countryForm.is_active };
            const url = this.countryForm.id ? `${cfg.countriesUrl}/${this.countryForm.id}` : cfg.countriesStore;
            const r = await fetch(url, { method: this.countryForm.id ? 'PUT' : 'POST', headers: this.headers(), credentials: 'same-origin', body: JSON.stringify(body) });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not save country.', false);
            this.flash(this.countryForm.id ? 'Country updated.' : 'Country added.');
            this.resetCountryForm();
            await this.reloadAll();
        },
        async deleteCountry(row) {
            if (!confirm(`Delete country "${row.name}"?`)) return;
            const r = await fetch(`${cfg.countriesUrl}/${row.id}`, { method: 'DELETE', headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not delete.', false);
            this.flash('Country deleted.');
            await this.reloadAll();
        },
        resetProvinceForm() {
            this.provinceForm = { id: null, country_id: this.countries[0]?.id || '', name: '', sort_order: 0, is_active: true };
        },
        editProvince(row) {
            this.provinceForm = { id: row.id, country_id: row.country_id, name: row.name, sort_order: row.sort_order || 0, is_active: row.is_active };
            this.section = 'provinces';
        },
        async saveProvince() {
            if (!this.provinceForm.country_id) {
                return this.flash('Please select a country.', false);
            }
            if (!(this.provinceForm.name || '').trim()) {
                return this.flash('Province name is required.', false);
            }
            const body = { country_id: this.provinceForm.country_id, name: this.provinceForm.name, sort_order: this.provinceForm.sort_order, is_active: this.provinceForm.is_active };
            const url = this.provinceForm.id ? `${cfg.provincesUrl}/${this.provinceForm.id}` : cfg.provincesStore;
            const r = await fetch(url, { method: this.provinceForm.id ? 'PUT' : 'POST', headers: this.headers(), credentials: 'same-origin', body: JSON.stringify(body) });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not save province.', false);
            this.flash(this.provinceForm.id ? 'Province updated.' : 'Province added.');
            this.resetProvinceForm();
            await this.reloadAll();
        },
        async deleteProvince(row) {
            if (!confirm(`Delete province "${row.name}"?`)) return;
            const r = await fetch(`${cfg.provincesUrl}/${row.id}`, { method: 'DELETE', headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not delete.', false);
            this.flash('Province deleted.');
            await this.reloadAll();
        },
        resetCityForm() {
            this.cityForm = { id: null, province_id: this.provinces[0]?.id || '', name: '', leopards_city_id: '', sort_order: 0, is_active: true };
        },
        editCity(row) {
            this.cityForm = { id: row.id, province_id: row.province_id, name: row.name, leopards_city_id: row.leopards_city_id || '', sort_order: row.sort_order || 0, is_active: row.is_active };
            this.section = 'cities';
        },
        async saveCity() {
            if (!this.cityForm.province_id) {
                return this.flash('Please select a province.', false);
            }
            if (!(this.cityForm.name || '').trim()) {
                return this.flash('City name is required.', false);
            }
            const body = { province_id: this.cityForm.province_id, name: this.cityForm.name, leopards_city_id: this.cityForm.leopards_city_id, sort_order: this.cityForm.sort_order, is_active: this.cityForm.is_active };
            const url = this.cityForm.id ? `${cfg.citiesUrl}/${this.cityForm.id}` : cfg.citiesStore;
            const r = await fetch(url, { method: this.cityForm.id ? 'PUT' : 'POST', headers: this.headers(), credentials: 'same-origin', body: JSON.stringify(body) });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not save city.', false);
            this.flash(this.cityForm.id ? 'City updated.' : 'City added.');
            this.resetCityForm();
            await this.reloadAll();
        },
        async deleteCity(row) {
            if (!confirm(`Delete city "${row.name}"?`)) return;
            const r = await fetch(`${cfg.citiesUrl}/${row.id}`, { method: 'DELETE', headers: this.headers(false), credentials: 'same-origin' });
            const data = await r.json();
            if (!r.ok || !data.success) return this.flash(data.message || 'Could not delete.', false);
            this.flash('City deleted.');
            await this.reloadAll();
        },
        async importLeopards() {
            if (!this.importProvinceId) return;
            this.importing = true;
            this.flash('Bulk importing cities from Leopards API…', true);
            const r = await fetch(cfg.importLeopardsUrl, {
                method: 'POST',
                headers: this.headers(),
                credentials: 'same-origin',
                body: JSON.stringify({ province_id: this.importProvinceId }),
            });
            const data = await r.json();
            this.importing = false;
            if (!r.ok || !data.success) return this.flash(data.message || 'Import failed.', false);
            this.flash(data.message || 'Import complete.');
            await this.reloadAll();
        },
        async syncLeopardsIds() {
            this.importing = true;
            this.flash('Matching Leopards city IDs to existing cities…', true);
            const r = await fetch(cfg.syncLeopardsIdsUrl, {
                method: 'POST',
                headers: this.headers(),
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            const data = await r.json();
            this.importing = false;
            if (!r.ok || !data.success) return this.flash(data.message || 'Sync failed.', false);
            this.flash(data.message || 'Sync complete.');
            await this.reloadAll();
        },
    }));
});
</script>
@endsection
