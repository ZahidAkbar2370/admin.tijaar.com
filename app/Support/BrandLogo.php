<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resolve brand logos for couriers and payment methods (custom upload or bundled default).
 */
class BrandLogo
{
    public static function resolve(?string $customPath, string $defaultAsset): string
    {
        $customPath = trim((string) $customPath);
        if ($customPath !== '') {
            $url = UploadHelper::url($customPath);
            if ($url) {
                return $url;
            }
        }

        $defaultAsset = ltrim($defaultAsset, '/');

        return asset($defaultAsset);
    }

    public static function courier(string $provider): string
    {
        $provider = CourierCatalog::normalize($provider);
        $meta = CourierCatalog::PROVIDERS[$provider] ?? null;
        if (! $meta) {
            return asset('assets/logos/couriers/default.svg');
        }

        $custom = (string) Setting::get($meta['logo_setting_key'], '');

        return self::resolve($custom !== '' ? $custom : null, $meta['default_logo']);
    }

    public static function paymentMethod(string $method): string
    {
        $methods = self::paymentMethodMeta();
        $meta = $methods[$method] ?? null;
        if (! $meta) {
            return asset('assets/logos/payment-methods/default.svg');
        }

        $custom = (string) Setting::get($meta['logo_setting_key'], '');

        return self::resolve($custom !== '' ? $custom : null, $meta['default_logo']);
    }

    /** @return array<string, array{logo_setting_key: string, default_logo: string}> */
    public static function paymentMethodMeta(): array
    {
        return [
            'cod' => [
                'logo_setting_key' => 'payment_cod_logo',
                'default_logo' => 'assets/logos/payment-methods/cod.svg',
            ],
            'jazzcash' => [
                'logo_setting_key' => 'payment_jazzcash_logo',
                'default_logo' => 'assets/logos/payment-methods/jazzcash.svg',
            ],
            'stripe' => [
                'logo_setting_key' => 'payment_stripe_logo',
                'default_logo' => 'assets/logos/payment-methods/stripe.svg',
            ],
            'paypal' => [
                'logo_setting_key' => 'payment_paypal_logo',
                'default_logo' => 'assets/logos/payment-methods/paypal.svg',
            ],
            'easypaisa' => [
                'logo_setting_key' => 'payment_easypaisa_logo',
                'default_logo' => 'assets/logos/payment-methods/easypaisa.svg',
            ],
        ];
    }

    public static function storeCourierLogo(string $provider, \Illuminate\Http\UploadedFile $file): string
    {
        $provider = CourierCatalog::normalize($provider);
        $meta = CourierCatalog::PROVIDERS[$provider] ?? null;
        if (! $meta) {
            throw new \InvalidArgumentException('Unknown courier provider.');
        }

        $key = $meta['logo_setting_key'];
        $old = Setting::get($key);
        UploadHelper::deleteAny($old);
        $path = UploadHelper::storePublic($file, 'settings/courier-logos');
        Setting::set($key, $path);

        return $path;
    }

    public static function removeCourierLogo(string $provider): void
    {
        $provider = CourierCatalog::normalize($provider);
        $meta = CourierCatalog::PROVIDERS[$provider] ?? null;
        if (! $meta) {
            return;
        }

        $key = $meta['logo_setting_key'];
        UploadHelper::deleteAny(Setting::get($key));
        Setting::set($key, '');
    }

    public static function storePaymentLogo(string $method, \Illuminate\Http\UploadedFile $file): string
    {
        $meta = self::paymentMethodMeta()[$method] ?? null;
        if (! $meta) {
            throw new \InvalidArgumentException('Unknown payment method.');
        }

        $key = $meta['logo_setting_key'];
        $old = Setting::get($key);
        UploadHelper::deleteAny($old);
        $path = UploadHelper::storePublic($file, 'settings/payment-logos');
        Setting::set($key, $path);

        return $path;
    }

    public static function removePaymentLogo(string $method): void
    {
        $meta = self::paymentMethodMeta()[$method] ?? null;
        if (! $meta) {
            return;
        }

        $key = $meta['logo_setting_key'];
        UploadHelper::deleteAny(Setting::get($key));
        Setting::set($key, '');
    }
}
