<?php

namespace App\Console\Commands;

use App\Services\CourierTrackingSyncService;
use Illuminate\Console\Command;

class SyncTcsTrackingCommand extends Command
{
    protected $signature = 'tcs:sync-tracking {--limit=50}';

    protected $description = 'Poll TCS tracking for seller-entered consignments and complete delivered orders';

    public function handle(): int
    {
        if (!CourierTrackingSyncService::isEnabled('tcs')) {
            $this->info('TCS Courier is disabled.');

            return self::SUCCESS;
        }

        $result = CourierTrackingSyncService::sync('tcs', (int) $this->option('limit'));

        $this->info("Synced {$result['updated']} of {$result['checked']} shipment(s).");

        return self::SUCCESS;
    }
}
