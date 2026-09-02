<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipe transactional / catalog data for a fresh marketplace DB
 * while keeping accounts and system settings.
 */
class FreshDatabaseKeepAccountsCommand extends Command
{
    protected $signature = 'db:fresh-keep-accounts
                            {--force : Run without confirmation}';

    protected $description = 'Remove orders, products, wallets activity, etc. Keep users (customer/seller/admin), sellers, stores, and system settings.';

    /** Tables whose rows must be preserved. */
    private array $keep = [
        'migrations',
        'users',
        'sellers',
        'stores',
        'settings',
        'abuse_safety_settings',
        'email_templates',
        'whatsapp_templates',
        'roles',
        'permissions',
        'role_permission',
        'user_role',
        'markets',
        'exchange_rates',
        'location_countries',
        'location_provinces',
        'location_cities',
        'shipping_zones',
        'shipping_rules',
        'pages',
        'faqs',
        'addresses',
        'social_accounts',
        'notification_preferences',
        'user_market_preferences',
        'two_factor_recovery_codes',
        'commissions',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will DELETE orders, products, carts, wallets activity, chats, etc. Keep users/sellers/stores/settings. Continue?')) {
            $this->warn('Cancelled.');

            return self::FAILURE;
        }

        $database = DB::getDatabaseName();
        $this->info("Database: {$database}");

        $all = collect(DB::select('SHOW TABLES'))
            ->map(function ($row) use ($database) {
                $key = 'Tables_in_' . $database;

                return $row->$key;
            })
            ->filter()
            ->values()
            ->all();

        $toWipe = array_values(array_diff($all, $this->keep));
        sort($toWipe);

        $this->line('Keeping ' . count($this->keep) . ' table(s) (if present).');
        $this->line('Wiping ' . count($toWipe) . ' table(s)...');

        Schema::disableForeignKeyConstraints();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $wiped = 0;
        foreach ($toWipe as $table) {
            try {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->line("  truncated {$table} ({$count} rows)");
                $wiped++;
            } catch (\Throwable $e) {
                // Some tables may not support truncate; fall back to delete
                try {
                    $deleted = DB::table($table)->delete();
                    $this->line("  deleted {$table} ({$deleted} rows) — " . $e->getMessage());
                    $wiped++;
                } catch (\Throwable $e2) {
                    $this->error("  FAILED {$table}: " . $e2->getMessage());
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Schema::enableForeignKeyConstraints();

        // Ensure seller wallets exist empty (optional recreate zero wallets)
        if (Schema::hasTable('wallets') && Schema::hasTable('users')) {
            // wallets was truncated; leave empty — created on demand
            $this->line('Wallets cleared (will recreate on next use).');
        }

        $this->newLine();
        $this->info("Done. Wiped {$wiped} tables.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['users', DB::table('users')->count()],
                ['sellers', Schema::hasTable('sellers') ? DB::table('sellers')->count() : 0],
                ['stores', Schema::hasTable('stores') ? DB::table('stores')->count() : 0],
                ['settings', Schema::hasTable('settings') ? DB::table('settings')->count() : 0],
                ['products', Schema::hasTable('products') ? DB::table('products')->count() : 0],
                ['orders', Schema::hasTable('orders') ? DB::table('orders')->count() : 0],
            ]
        );

        return self::SUCCESS;
    }
}
