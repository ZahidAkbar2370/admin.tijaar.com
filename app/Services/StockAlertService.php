<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\OutOfStockAlertNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAlertService
{
    /**
     * After stock changes: fire out-of-stock / low-stock alerts, or clear them when restocked.
     * Also auto-unpublish when OOS and restore when restocked (only if we auto-unpublished).
     */
    public static function syncForProduct(Product $product, ?int $quantityBefore = null): void
    {
        $product->refresh();
        if (!$product->relationLoaded('variants')) {
            $product->load('variants');
        }

        $after = $product->getAvailableQuantity();
        $tracked = $product->track_inventory !== false;

        if (!$tracked) {
            self::clearOutOfStockAlerts($product->id);
            return;
        }

        if ($after <= 0) {
            self::markInactiveDueToOos($product);
            self::notifyOutOfStock($product);
            return;
        }

        // Restocked — clear OOS alerts and restore listing if we auto-deactivated it
        if ($quantityBefore !== null && $quantityBefore <= 0 && $after > 0) {
            self::clearOutOfStockAlerts($product->id);
        } elseif ($after > 0) {
            self::clearOutOfStockAlerts($product->id);
        }

        self::restoreActiveAfterRestock($product);

        $threshold = (int) ($product->low_stock_threshold ?? 0);
        if ($threshold > 0 && $after > 0 && $after <= $threshold) {
            $user = $product->store?->seller?->user ?? $product->sellerUser;
            if ($user) {
                try {
                    $user->notify(new LowStockAlertNotification($product));
                } catch (\Throwable $e) {
                    Log::warning('Low stock alert failed: ' . $e->getMessage());
                }
            }
        }
    }

    /** Published → unpublished when stock hits zero (hidden from catalog). */
    public static function markInactiveDueToOos(Product $product): void
    {
        if ($product->status !== 'published') {
            return;
        }

        $product->status = 'unpublished';
        $product->oos_auto_inactive = true;
        $product->saveQuietly();
    }

    /** Restore unpublished→published only when previously auto-deactivated for OOS. */
    public static function restoreActiveAfterRestock(Product $product): void
    {
        if (!$product->oos_auto_inactive) {
            return;
        }
        if (!in_array($product->status, ['unpublished'], true)) {
            // Still clear the flag if status was changed manually
            if ($product->oos_auto_inactive) {
                $product->oos_auto_inactive = false;
                $product->saveQuietly();
            }
            return;
        }

        $product->status = 'published';
        $product->oos_auto_inactive = false;
        $product->saveQuietly();
    }

    public static function notifyOutOfStock(Product $product): void
    {
        // Avoid duplicate unread OOS alerts for the same product/user
        $seller = $product->store?->seller?->user ?? $product->sellerUser;
        $recipients = collect();
        if ($seller) {
            $recipients->push($seller);
        }
        $recipients = $recipients->merge(User::where('role', 'admin')->get())->unique('id');

        foreach ($recipients as $user) {
            try {
                $hasOpen = $user->unreadNotifications()
                    ->where('type', OutOfStockAlertNotification::class)
                    ->get()
                    ->contains(fn ($n) => (int) ($n->data['product_id'] ?? 0) === (int) $product->id);

                if ($hasOpen) {
                    continue;
                }
                $user->notify(new OutOfStockAlertNotification($product));
            } catch (\Throwable $e) {
                Log::warning('Out of stock alert failed: ' . $e->getMessage());
            }
        }
    }

    public static function clearOutOfStockAlerts(int $productId): void
    {
        try {
            DB::table('notifications')
                ->where('type', OutOfStockAlertNotification::class)
                ->where(function ($q) use ($productId) {
                    $q->where('data->product_id', $productId)
                        ->orWhere('data', 'like', '%"product_id":' . $productId . '%')
                        ->orWhere('data', 'like', '%"product_id":"' . $productId . '"%');
                })
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('Clear out-of-stock alerts failed: ' . $e->getMessage());
        }
    }
}
