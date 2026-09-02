<?php

namespace Database\Seeders;

use App\Models\Commission;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
{
    public function run(): void
    {
        Commission::firstOrCreate(
            ['scope_type' => 'global'],
            [
                'scope_id' => null,
                'seller_type' => null,
                'commission_type' => 'percentage',
                'value' => 5,
                'priority' => 0,
                'is_active' => true,
            ]
        );
    }
}
