<?php

namespace App\Console\Commands;

use App\Models\ReservedStock;
use Illuminate\Console\Command;

class ReleaseReservedStockCommand extends Command
{
    protected $signature = 'inventory:release-reserved';
    protected $description = 'Release expired reserved stock (cart lock)';

    public function handle(): int
    {
        $count = ReservedStock::where('expires_at', '<', now())->delete();
        $this->info("Released {$count} expired reservation(s).");
        return 0;
    }
}
