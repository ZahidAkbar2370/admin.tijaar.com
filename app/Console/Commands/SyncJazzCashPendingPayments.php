<?php

namespace App\Console\Commands;

use App\Services\JazzCashService;
use Illuminate\Console\Command;

class SyncJazzCashPendingPayments extends Command
{
    protected $signature = 'jazzcash:sync-pending {--limit=50 : Max pending payments to check}';

    protected $description = 'Inquire JazzCash for pending payment status (Status Inquiry API)';

    public function handle(JazzCashService $jazzCash): int
    {
        $limit = (int) $this->option('limit');
        $updated = $jazzCash->syncPendingPayments($limit);
        $this->info("JazzCash pending sync complete. Updated: {$updated}");

        return self::SUCCESS;
    }
}
