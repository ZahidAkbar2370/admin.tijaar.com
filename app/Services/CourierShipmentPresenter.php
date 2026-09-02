<?php

namespace App\Services;

use App\Models\Shipment;
use App\Support\CourierCatalog;

/**
 * Enrich shipment payloads for API/admin with public tracking links (no booking/drop-off docs).
 */
class CourierShipmentPresenter
{
    public static function enrich(Shipment $shipment): void
    {
        $carrier = CourierCatalog::normalize((string) ($shipment->carrier ?? ''));
        $cn = self::cnNumber($shipment);

        if ($cn !== '') {
            $shipment->tracking_url = $shipment->tracking_url
                ?: CourierCatalog::publicTrackingUrl($carrier, $cn);
        }

        // Keep attribute for older clients; no booking/drop-off instructions.
        $shipment->setAttribute('courier_dropoff', $cn !== '' ? [
            'cn_number' => $cn,
            'tracking_url' => $shipment->tracking_url,
        ] : null);
        $shipment->setAttribute('lcs_dropoff', null);
    }

    public static function cnNumber(Shipment $shipment): string
    {
        $carrier = CourierCatalog::normalize((string) ($shipment->carrier ?? ''));

        if ($carrier === 'tcs' || $shipment->tcs_cn_number) {
            return (string) ($shipment->tcs_cn_number ?? $shipment->tracking_number ?? '');
        }

        if ($carrier === 'leopards' || $shipment->lcs_cn_number) {
            return (string) ($shipment->lcs_cn_number ?? $shipment->tracking_number ?? '');
        }

        return (string) ($shipment->tracking_number ?? '');
    }

    public static function cnLabel(Shipment $shipment): string
    {
        return CourierCatalog::label((string) ($shipment->carrier ?? '')).' CN';
    }

    public static function carrierLabel(Shipment $shipment): string
    {
        $carrier = CourierCatalog::normalize((string) ($shipment->carrier ?? ''));

        return $carrier !== ''
            ? CourierCatalog::label($carrier)
            : 'Courier';
    }

    public static function trackingUrl(Shipment $shipment): ?string
    {
        if ($shipment->tracking_url) {
            return (string) $shipment->tracking_url;
        }

        $cn = self::cnNumber($shipment);
        if ($cn === '') {
            return null;
        }

        $url = CourierCatalog::publicTrackingUrl((string) $shipment->carrier, $cn);

        return $url !== '' ? $url : null;
    }
}
