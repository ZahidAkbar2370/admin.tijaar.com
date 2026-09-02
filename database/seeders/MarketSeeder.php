<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        Market::upsert([
            ['code' => 'PK', 'name' => 'Pakistan', 'currency_code' => 'PKR', 'currency_symbol' => '₨', 'priority' => 1, 'is_active' => true],
            ['code' => 'AE', 'name' => 'UAE', 'currency_code' => 'AED', 'currency_symbol' => 'د.إ', 'priority' => 2, 'is_active' => true],
        ], ['code'], ['name', 'currency_code', 'currency_symbol', 'priority', 'is_active']);

        ExchangeRate::updateOrCreate(
            ['from_currency' => 'PKR', 'to_currency' => 'AED'],
            ['rate' => 0.013, 'effective_from' => now(), 'effective_until' => null]
        );
    }
}
