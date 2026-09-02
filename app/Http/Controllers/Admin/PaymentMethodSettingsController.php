<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\JazzCashService;
use App\Support\BrandLogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

/**
 * One admin page per payment method (COD + each gateway).
 *
 * Every page is driven by the field map below so the markup, defaults and
 * validation stay in one place. Keys match the existing Setting keys.
 */
class PaymentMethodSettingsController extends Controller
{
    /** Blank submissions keep the stored value instead of wiping the credential. */
    protected const SECRET_KEYS = [
        'jazzcash_password', 'jazzcash_integrity_salt',
        'stripe_secret', 'stripe_webhook_secret',
        'paypal_client_secret', 'easypaisa_hash_key',
    ];

    public function index(): View
    {
        $items = [];
        foreach ($this->methods() as $key => $config) {
            $enabledKey = match ($key) {
                'cod' => 'payment_cod_enabled',
                'jazzcash' => 'jazzcash_enabled',
                'stripe' => 'stripe_enabled',
                'paypal' => 'paypal_enabled',
                'easypaisa' => 'easypaisa_enabled',
                default => null,
            };
            $items[] = [
                'key' => $key,
                'label' => $config['label'],
                'description' => $config['description'] ?? '',
                'route' => 'admin.payment-methods.'.$key,
                'logo_url' => BrandLogo::paymentMethod($key),
                'has_custom_logo' => $this->paymentMethodHasCustomLogo($key),
                'enabled' => $enabledKey
                    ? (string) Setting::get($enabledKey, config('settings_defaults.'.$enabledKey, '0')) === '1'
                    : false,
            ];
        }

        return view('admin.payment-methods.index', compact('items'));
    }

    public function cod(): View
    {
        return $this->render('cod');
    }

    public function updateCod(Request $request): RedirectResponse
    {
        return $this->persist($request, 'cod');
    }

    public function jazzcash(): View
    {
        return $this->render('jazzcash');
    }

    public function updateJazzcash(Request $request): RedirectResponse
    {
        return $this->persist($request, 'jazzcash');
    }

    public function stripe(): View
    {
        return $this->render('stripe');
    }

    public function updateStripe(Request $request): RedirectResponse
    {
        return $this->persist($request, 'stripe');
    }

    public function paypal(): View
    {
        return $this->render('paypal');
    }

    public function updatePaypal(Request $request): RedirectResponse
    {
        return $this->persist($request, 'paypal');
    }

    public function easypaisa(): View
    {
        return $this->render('easypaisa');
    }

    public function updateEasypaisa(Request $request): RedirectResponse
    {
        return $this->persist($request, 'easypaisa');
    }

    public function testJazzcash(Request $request): JsonResponse
    {
        $overrides = array_filter([
            'merchant_id' => $request->input('merchant_id'),
            'password' => $request->input('password'),
            'integrity_salt' => $request->input('integrity_salt'),
            'checkout_url' => $request->input('checkout_url'),
            'return_url' => $request->input('return_url'),
        ], fn ($v) => $v !== null && $v !== '');

        $result = (new JazzCashService())->testConnection($overrides);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    protected function render(string $method): View
    {
        $config = $this->methods()[$method];

        $stored = Setting::pluck('value', 'key')->toArray();
        $settings = [];
        foreach (array_keys($config['fields']) as $key) {
            $settings[$key] = (string) ($stored[$key] ?? config('settings_defaults.' . $key, ''));
        }

        return view('admin.payment-methods.show', [
            'method' => $method,
            'config' => $config,
            'settings' => $settings,
            'logo_url' => BrandLogo::paymentMethod($method),
            'has_custom_logo' => $this->paymentMethodHasCustomLogo($method),
        ]);
    }

    protected function persist(Request $request, string $method): RedirectResponse
    {
        $config = $this->methods()[$method];

        $rules = [];
        foreach ($config['fields'] as $key => $field) {
            if (!empty($field['rules'])) {
                $rules['settings.' . $key] = $field['rules'];
            }
        }
        $rules['logo'] = 'nullable|file|mimes:jpeg,jpg,png,webp,svg|max:2048';
        $rules['remove_logo'] = 'nullable|in:0,1';
        $request->validate($rules);

        $input = (array) $request->input('settings', []);
        foreach ($config['fields'] as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = is_array($input[$key]) ? json_encode($input[$key]) : (string) ($input[$key] ?? '');
            if (in_array($key, self::SECRET_KEYS, true) && trim($value) === '') {
                continue;
            }
            if ($key === 'partial_payment_online_percent') {
                $value = (string) max(1, min(99, (int) $value));
            }
            Setting::set($key, $value);
        }

        if ($request->boolean('remove_logo')) {
            BrandLogo::removePaymentLogo($method);
        } elseif ($request->hasFile('logo')) {
            BrandLogo::storePaymentLogo($method, $request->file('logo'));
        }

        $this->syncConfigToRuntime();

        return back()->with('success', $config['label'] . ' settings saved.');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function methods(): array
    {
        return [
            'cod' => [
                'label' => 'Cash on Delivery',
                'description' => 'Let customers pay the courier on delivery. Partial payment collects part of the total online and the rest as cash.',
                'fields' => [
                    'payment_cod_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Cash on Delivery (COD)',
                        'help' => 'When off, COD is hidden at checkout and every order must be paid online.',
                        'rules' => 'required|in:0,1',
                    ],
                    'partial_payment_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable partial payment (online + COD)',
                        'help' => 'Customer pays a share online and the remainder in cash on delivery.',
                        'rules' => 'required|in:0,1',
                    ],
                    'partial_payment_online_percent' => [
                        'type' => 'number',
                        'label' => 'Online share (%)',
                        'help' => 'Percentage charged online when partial payment is used. The rest is collected as cash on delivery.',
                        'attrs' => ['min' => 1, 'max' => 99],
                        'rules' => 'required|integer|min:1|max:99',
                    ],
                ],
            ],
            'jazzcash' => [
                'label' => 'JazzCash',
                'description' => 'Mobile wallet payments for Pakistan. Credentials come from the JazzCash merchant portal.',
                'docs_url' => 'https://onlinepayments.jazzcash.com.pk/sandbox-frontend/',
                'docs_label' => 'JazzCash sandbox portal',
                'fields' => [
                    'jazzcash_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable JazzCash',
                        'rules' => 'required|in:0,1',
                    ],
                    'jazzcash_checkout_mode' => [
                        'type' => 'select',
                        'label' => 'Checkout mode',
                        'help' => 'MWallet v2.0 is recommended — checkout collects the JazzCash mobile number and CNIC with no portal redirect.',
                        'options' => [
                            'mwallet_v2' => 'MWallet REST v2.0 (recommended)',
                            'mwallet_v1' => 'MWallet REST v1.1 (mobile only)',
                            'portal' => 'Payment Portal redirect (legacy)',
                        ],
                        'rules' => 'required|in:mwallet_v2,mwallet_v1,portal',
                    ],
                    'jazzcash_merchant_id' => ['type' => 'text', 'label' => 'Merchant ID', 'rules' => 'nullable|string|max:190'],
                    'jazzcash_password' => ['type' => 'password', 'label' => 'Password'],
                    'jazzcash_integrity_salt' => ['type' => 'password', 'label' => 'Integrity Salt'],
                    'jazzcash_return_url' => [
                        'type' => 'url',
                        'label' => 'Return / callback URL',
                        'help' => 'Must point at your Tijaar server, not the JazzCash simulator. Register the same URL in the merchant portal.',
                        'rules' => 'nullable|url|max:255',
                    ],
                    'jazzcash_mwallet_v2_url' => ['type' => 'url', 'label' => 'MWallet API URL (v2.0)', 'placeholder' => 'Leave blank for default', 'rules' => 'nullable|url|max:255'],
                    'jazzcash_mwallet_url' => ['type' => 'url', 'label' => 'MWallet API URL (v1.1)', 'placeholder' => 'Leave blank for default', 'rules' => 'nullable|url|max:255'],
                    'jazzcash_checkout_url' => ['type' => 'url', 'label' => 'Checkout URL (legacy portal)', 'placeholder' => 'Leave blank for default', 'rules' => 'nullable|url|max:255'],
                    'jazzcash_status_inquiry_url' => [
                        'type' => 'url',
                        'label' => 'Status Inquiry API URL',
                        'help' => 'Used to reconcile pending payments. Leave blank for default.',
                        'rules' => 'nullable|url|max:255',
                    ],
                ],
            ],
            'stripe' => [
                'label' => 'Stripe',
                'description' => 'Card payments. PKR order totals are converted to USD with the rate below.',
                'docs_url' => 'https://dashboard.stripe.com/apikeys',
                'docs_label' => 'Stripe API keys',
                'fields' => [
                    'stripe_enabled' => ['type' => 'toggle', 'label' => 'Enable Stripe (card payments)', 'rules' => 'required|in:0,1'],
                    'stripe_key' => ['type' => 'text', 'label' => 'Publishable key', 'rules' => 'nullable|string|max:255'],
                    'stripe_secret' => ['type' => 'password', 'label' => 'Secret key'],
                    'stripe_webhook_secret' => ['type' => 'password', 'label' => 'Webhook signing secret'],
                    'stripe_pkr_to_usd' => [
                        'type' => 'number',
                        'label' => 'PKR per 1 USD',
                        'help' => 'Conversion rate used to charge PKR order totals in USD.',
                        'attrs' => ['min' => 1, 'step' => '0.01'],
                        'rules' => 'required|numeric|min:1|max:100000',
                    ],
                ],
            ],
            'paypal' => [
                'label' => 'PayPal',
                'description' => 'PayPal checkout for international buyers. Use sandbox credentials until you go live.',
                'docs_url' => 'https://developer.paypal.com/dashboard/applications',
                'docs_label' => 'PayPal developer dashboard',
                'fields' => [
                    'paypal_enabled' => ['type' => 'toggle', 'label' => 'Enable PayPal', 'rules' => 'required|in:0,1'],
                    'paypal_mode' => [
                        'type' => 'select',
                        'label' => 'Mode',
                        'options' => ['sandbox' => 'Sandbox', 'live' => 'Live'],
                        'rules' => 'required|in:sandbox,live',
                    ],
                    'paypal_client_id' => ['type' => 'text', 'label' => 'Client ID', 'rules' => 'nullable|string|max:255'],
                    'paypal_client_secret' => ['type' => 'password', 'label' => 'Client secret'],
                ],
            ],
            'easypaisa' => [
                'label' => 'Easypaisa',
                'description' => 'Easypaisa / Easypay wallet payments. Store ID and hash key come from the merchant portal.',
                'fields' => [
                    'easypaisa_enabled' => ['type' => 'toggle', 'label' => 'Enable Easypaisa', 'rules' => 'required|in:0,1'],
                    'easypaisa_store_id' => ['type' => 'text', 'label' => 'Store ID', 'rules' => 'nullable|string|max:190'],
                    'easypaisa_hash_key' => ['type' => 'password', 'label' => 'Hash key'],
                    'easypaisa_checkout_url' => ['type' => 'url', 'label' => 'Checkout URL', 'placeholder' => 'Leave blank for default', 'rules' => 'nullable|url|max:255'],
                    'easypaisa_postback_url' => ['type' => 'url', 'label' => 'Postback URL', 'placeholder' => 'Leave blank for default', 'rules' => 'nullable|url|max:255'],
                ],
            ],
        ];
    }

    protected function paymentMethodHasCustomLogo(string $method): bool
    {
        $meta = BrandLogo::paymentMethodMeta()[$method] ?? null;
        if (! $meta) {
            return false;
        }

        return trim((string) Setting::get($meta['logo_setting_key'], '')) !== '';
    }

    protected function syncConfigToRuntime(): void
    {
        $map = [
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
            'easypaisa_store_id' => 'services.easypaisa.store_id',
            'easypaisa_hash_key' => 'services.easypaisa.hash_key',
            'easypaisa_checkout_url' => 'services.easypaisa.checkout_url',
            'easypaisa_postback_url' => 'services.easypaisa.postback_url',
        ];

        foreach ($map as $settingKey => $configKey) {
            $v = Setting::get($settingKey);
            if ($v !== null && $v !== '') {
                Config::set($configKey, $v);
            }
        }
    }
}
