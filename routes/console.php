<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wishlist:alerts')->daily();
Schedule::command('inventory:low-stock-alert')->daily();
Schedule::command('inventory:release-reserved')->hourly();
Schedule::command('payouts:release-held')->daily();
Schedule::command('lcs:sync-tracking')->hourly()->withoutOverlapping();
Schedule::command('tcs:sync-tracking')->hourly()->withoutOverlapping();
Schedule::command('jazzcash:sync-pending')->everyFifteenMinutes();
Schedule::command('promotions:expire')->hourly();
