<?php

namespace App\Console\Commands;

use App\Services\CourierTrackingSyncService;
use Illuminate\Console\Command;

class SyncLcsTrackingCommand extends Command
{
    protected $signature = 'lcs:sync-tracking {--limit=50}';

    protected $description = 'Poll Leopards tracking for seller-entered consignments and complete delivered orders';

    public function handle(): int
    {
        if (!CourierTrackingSyncService::isEnabled('leopards')) {
            $this->info('Leopards Courier is disabled.');

            return self::SUCCESS;
        }

        $result = CourierTrackingSyncService::sync('leopards', (int) $this->option('limit'));

        $this->info("Synced {$result['updated']} of {$result['checked']} shipment(s).");

        return self::SUCCESS;
    }
}
