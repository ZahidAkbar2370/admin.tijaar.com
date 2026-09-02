<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class LowStockAlertCommand extends Command
{
    protected $signature = 'inventory:low-stock-alert';
    protected $description = 'Send low stock alerts for products below threshold';

    public function handle(): int
    {
        $products = Product::where('track_inventory', true)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->with(['store.seller.user'])
            ->get();

        foreach ($products as $product) {
            $user = $product->store?->seller?->user ?? $product->sellerUser;
            if ($user) {
                try {
                    $user->notify(new \App\Notifications\LowStockAlertNotification($product));
                } catch (\Throwable $e) {
                    \Log::warning('Low stock alert failed: ' . $e->getMessage());
                }
            }
        }

        $this->info("Processed {$products->count()} low stock alerts.");
        return 0;
    }
}
