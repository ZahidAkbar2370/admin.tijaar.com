<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Retained only so jobs queued before manual tracking was introduced drain cleanly.
 */
class CreateLcsShipmentsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        Log::info('CreateLcsShipmentsJob skipped — courier booking is manual now.', ['order_id' => $this->orderId]);
    }
}
