<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;

/**
 * Courier auto-booking is retired. Sellers hand the parcel to TCS or Leopards
 * themselves and enter the tracking ID in the seller panel; Tijaar only polls
 * the tracking APIs afterwards.
 *
 * The class is kept so existing callers keep working as no-ops.
 */
class CourierBookingService
{
    public const MANUAL_TRACKING_MESSAGE = 'Manual tracking only — hand the parcel to the courier and enter the tracking number.';

    public static function activeProvider(): string
    {
        return PkCourierShippingService::activeProvider();
    }

    public static function providerForOrder(Order $order): string
    {
        $order->loadMissing('shipments');

        // Prefer shipment carrier (what the seller actually selected).
        foreach ($order->shipments as $shipment) {
            $carrier = strtolower(trim((string) ($shipment->carrier ?? '')));
            if ($carrier === 'tcs' || str_contains($carrier, 'tcs')) {
                return 'tcs';
            }
            if ($carrier === 'leopards' || $carrier === 'lcs' || str_contains($carrier, 'leopard')) {
                return 'leopards';
            }
        }

        $method = strtolower(trim((string) ($order->shipping_method ?? '')));
        if ($method === 'tcs' || str_contains($method, 'tcs')) {
            return 'tcs';
        }
        if ($method === 'leopards' || $method === 'lcs' || str_contains($method, 'leopard')) {
            return 'leopards';
        }

        return self::activeProvider();
    }

    /** No-op: courier booking is manual. */
    public static function dispatchIfEligible(Order $order): void
    {
        Log::debug('Courier auto-booking skipped (manual tracking only).', ['order_id' => $order->id]);
    }

    /** No-op: courier booking is manual. */
    public static function createShipmentsForOrder(int $orderId): void
    {
        Log::debug('Courier shipment creation skipped (manual tracking only).', ['order_id' => $orderId]);
    }

    public static function bookingErrorMessage(?Shipment $shipment): ?string
    {
        return null;
    }
}
