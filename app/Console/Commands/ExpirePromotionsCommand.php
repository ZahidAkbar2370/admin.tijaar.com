<?php

namespace App\Console\Commands;

use App\Services\PromotionExpirationService;
use Illuminate\Console\Command;

class ExpirePromotionsCommand extends Command
{
    protected $signature = 'promotions:expire';

    protected $description = 'Expire promotion packages past end date and remove featured/hot flags';

    public function handle(): int
    {
        $count = PromotionExpirationService::expireDue();
        $this->info("Expired {$count} promotion(s).");

        return self::SUCCESS;
    }
}
