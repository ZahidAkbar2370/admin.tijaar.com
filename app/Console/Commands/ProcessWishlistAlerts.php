<?php

namespace App\Console\Commands;

use App\Models\Wishlist;
use App\Notifications\WishlistPriceDropNotification;
use Illuminate\Console\Command;

class ProcessWishlistAlerts extends Command
{
    protected $signature = 'wishlist:alerts';
    protected $description = 'Process price drop and back-in-stock wishlist alerts';

    public function handle(): int
    {
        $priceAlerts = Wishlist::where('price_alert', true)->with(['product', 'user'])->get();
        $count = 0;

        foreach ($priceAlerts as $wishlist) {
            if (!$wishlist->user_id || !$wishlist->product) continue;

            $product = $wishlist->product;
            $oldPrice = $product->compare_at_price ?: $product->price;
            $newPrice = (float) $product->price;

            if ($product->compare_at_price && $newPrice < (float) $product->compare_at_price) {
                try {
                    $wishlist->user->notify(new WishlistPriceDropNotification($product, $oldPrice, $newPrice));
                    $count++;
                } catch (\Throwable $e) {
                    \Log::warning("Wishlist price alert failed: " . $e->getMessage());
                }
            }
        }

        $stockAlerts = Wishlist::where('stock_alert', true)->with(['product', 'user'])->get();
        foreach ($stockAlerts as $wishlist) {
            if (!$wishlist->user_id || !$wishlist->product) continue;
            if ($wishlist->product->quantity > 0) {
                try {
                    $wishlist->user->notify(new \App\Notifications\WishlistBackInStockNotification($wishlist->product));
                    $count++;
                } catch (\Throwable $e) {
                    \Log::warning("Wishlist stock alert failed: " . $e->getMessage());
                }
            }
        }

        $this->info("Sent {$count} wishlist alerts.");
        return 0;
    }
}
