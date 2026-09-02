<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Couriers available on Tijaar for seller tracking (enable/disable in Admin → Courier).
 * API booking / rate / credential integrations are not used; tracking sync comes later via queue.
 */
class CourierCatalog
{
    public const PROVIDERS = [
        'tcs' => [
            'value' => 'tcs',
            'label' => 'TCS',
            'setting_key' => 'tcs_enabled',
            'logo_setting_key' => 'courier_tcs_logo',
            'default_logo' => 'assets/logos/couriers/tcs.svg',
            'description' => 'TCS Express Pakistan',
        ],
        'leopards' => [
            'value' => 'leopards',
            'label' => 'Leopard / LCS',
            'setting_key' => 'leopards_enabled',
            'logo_setting_key' => 'courier_leopards_logo',
            'default_logo' => 'assets/logos/couriers/leopards.svg',
            'description' => 'Leopard Courier Services (LCS)',
        ],
        'postex' => [
            'value' => 'postex',
            'label' => 'PostEx',
            'setting_key' => 'postex_enabled',
            'logo_setting_key' => 'courier_postex_logo',
            'default_logo' => 'assets/logos/couriers/postex.svg',
            'description' => 'PostEx Pakistan',
        ],
        'dex' => [
            'value' => 'dex',
            'label' => 'Dex',
            'setting_key' => 'dex_enabled',
            'logo_setting_key' => 'courier_dex_logo',
            'default_logo' => 'assets/logos/couriers/dex.svg',
            'description' => 'Dex Courier',
        ],
        'daewoo_fastex' => [
            'value' => 'daewoo_fastex',
            'label' => 'Daewoo FastEx',
            'setting_key' => 'daewoo_fastex_enabled',
            'logo_setting_key' => 'courier_daewoo_fastex_logo',
            'default_logo' => 'assets/logos/couriers/daewoo-fastex.svg',
            'description' => 'Daewoo Express cargo & courier',
        ],
        'mnp' => [
            'value' => 'mnp',
            'label' => 'M&P',
            'setting_key' => 'mnp_enabled',
            'logo_setting_key' => 'courier_mnp_logo',
            'default_logo' => 'assets/logos/couriers/mnp.svg',
            'description' => 'M&P Express Logistics (Mulphilog)',
        ],
        'baloch_cargo' => [
            'value' => 'baloch_cargo',
            'label' => 'Baloch Cargo',
            'setting_key' => 'baloch_cargo_enabled',
            'logo_setting_key' => 'courier_baloch_cargo_logo',
            'default_logo' => 'assets/logos/couriers/baloch-cargo.svg',
            'description' => 'Baloch Cargo & Logistics',
        ],
    ];

    /** @return list<array{value: string, label: string, setting_key: string, enabled: bool, logo_url: string, has_custom_logo: bool}> */
    public static function all(): array
    {
        $out = [];
        foreach (self::PROVIDERS as $provider) {
            $customLogo = (string) Setting::get($provider['logo_setting_key'], '');
            $out[] = [
                ...$provider,
                'enabled' => self::isEnabled($provider['value']),
                'logo_url' => BrandLogo::courier($provider['value']),
                'has_custom_logo' => $customLogo !== '',
            ];
        }

        return $out;
    }

    /** @return list<array{value: string, label: string, logo: string}> */
    public static function enabled(): array
    {
        return array_values(array_map(
            fn (array $p) => [
                'value' => $p['value'],
                'label' => $p['label'],
                'logo' => BrandLogo::courier($p['value']),
            ],
            array_filter(self::all(), fn (array $p) => $p['enabled'])
        ));
    }

    /** @return list<string> */
    public static function enabledValues(): array
    {
        return array_column(self::enabled(), 'value');
    }

    public static function isEnabled(string $provider): bool
    {
        $provider = self::normalize($provider);
        $meta = self::PROVIDERS[$provider] ?? null;
        if (! $meta) {
            return false;
        }

        return (string) Setting::get($meta['setting_key'], '0') === '1';
    }

    public static function isValid(?string $provider): bool
    {
        $provider = self::normalize((string) $provider);

        return in_array($provider, self::enabledValues(), true);
    }

    public static function label(string $provider): string
    {
        $provider = self::normalize($provider);

        return self::PROVIDERS[$provider]['label'] ?? ucfirst($provider);
    }

    public static function normalize(string $provider): string
    {
        $provider = strtolower(trim($provider));

        return match ($provider) {
            'lcs', 'leopard', 'leopard_courier', 'leopards_courier' => 'leopards',
            'tcs_courier' => 'tcs',
            'post_ex', 'post-ex' => 'postex',
            'dex_courier', 'pk_dex', 'daraz_express' => 'dex',
            'daewoo', 'daewoo_express', 'fastex' => 'daewoo_fastex',
            'mp', 'm_p', 'm&p', 'mulphilog', 'mp_courier' => 'mnp',
            'baloch', 'baloch_transport', 'balochcargo' => 'baloch_cargo',
            default => $provider,
        };
    }

    /** Public tracking page (no API credentials). */
    public static function publicTrackingUrl(string $provider, ?string $trackingNumber = null): string
    {
        $provider = self::normalize($provider);
        $cn = trim((string) $trackingNumber);

        return match ($provider) {
            'tcs' => $cn !== ''
                ? 'https://www.tcsexpress.com/track/'.rawurlencode($cn)
                : 'https://www.tcsexpress.com/track',
            'leopards' => $cn !== ''
                ? 'https://merchantapi.leopardscourier.com/api/trackBookedPacket/format/json/?track_numbers='.rawurlencode($cn)
                : 'https://www.leopardscourier.com/tracking',
            'postex' => $cn !== ''
                ? 'https://postex.pk/tracking?cn='.rawurlencode($cn)
                : 'https://postex.pk/tracking',
            'dex' => $cn !== ''
                ? 'https://www.daraz.pk/?tracking='.rawurlencode($cn)
                : 'https://www.daraz.pk/',
            'daewoo_fastex' => $cn !== ''
                ? 'https://daewoofastex.pk/?consignment='.rawurlencode($cn)
                : 'https://daewoofastex.pk/',
            'mnp' => $cn !== ''
                ? 'https://mulphilog.com.pk/track-shipment.php?cn='.rawurlencode($cn)
                : 'https://mulphilog.com.pk/track-shipment.php',
            'baloch_cargo' => $cn !== ''
                ? 'https://balochtransport.com/?tracking='.rawurlencode($cn)
                : 'https://balochtransport.com/',
            default => '',
        };
    }
}
