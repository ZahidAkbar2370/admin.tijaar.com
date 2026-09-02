<?php

namespace App\Console\Commands;

use App\Services\JazzCashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareJazzCashVerifyLog extends Command
{
    protected $signature = 'jazzcash:prepare-verify-log';

    protected $description = 'Clear laravel.log for JazzCash IPN / Status Inquiry verification video';

    public function handle(JazzCashService $jazzCash): int
    {
        $path = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');

        $jazzCash->logVerification('Ready — place sandbox order; watch Status Inquiry + IPN lines only');

        $this->info('Cleared: ' . $path);
        $this->info('tail -f storage/logs/laravel.log | grep --line-buffered "JAZZCASH VERIFY"');

        return self::SUCCESS;
    }
}
