<?php

namespace App\Services;

use App\Support\CourierCatalog;
use Illuminate\Support\Facades\Log;

/**
 * Courier tracking sync placeholder.
 *
 * Automatic status polling will run via queued events / dedicated tracking
 * endpoints later. Until then this service only reports whether the courier
 * is enabled for seller dropdowns — it does not call TCS/LCS APIs.
 */
class CourierTrackingSyncService
{
    public static function isEnabled(string $carrier): bool
    {
        return CourierCatalog::isEnabled($carrier);
    }

    /**
     * @return array{checked: int, updated: int, message?: string}
     */
    public static function sync(string $carrier, int $limit = 50): array
    {
        $carrier = CourierCatalog::normalize($carrier);

        Log::info('CourierTrackingSyncService skipped — tracking queue endpoints not wired yet.', [
            'carrier' => $carrier,
            'limit' => $limit,
        ]);

        return [
            'checked' => 0,
            'updated' => 0,
            'message' => 'Tracking sync will run via queue endpoints later.',
        ];
    }
}
