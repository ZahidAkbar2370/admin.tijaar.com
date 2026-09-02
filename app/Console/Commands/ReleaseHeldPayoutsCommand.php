<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\WalletTransaction;
use App\Services\PayoutService;
use Illuminate\Console\Command;

class ReleaseHeldPayoutsCommand extends Command
{
    protected $signature = 'payouts:release-held';
    protected $description = 'Credit seller wallets for delivered shipments/orders that passed the payout holding period';

    public function handle(): int
    {
        $creditedShipmentIds = WalletTransaction::where('reference_type', 'shipment_delivery')
            ->pluck('reference_id')
            ->unique()
            ->toArray();

        // Candidate pool: creditSellerForDeliveredShipment enforces per-user hold days.
        $shipments = Shipment::with(['order.items', 'store.seller'])
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotIn('id', $creditedShipmentIds ?: [0])
            ->limit(200)
            ->get();

        $shipmentCount = 0;
        foreach ($shipments as $shipment) {
            try {
                $before = WalletTransaction::where('reference_type', 'shipment_delivery')
                    ->where('reference_id', $shipment->id)
                    ->exists();
                PayoutService::creditSellerForDeliveredShipment($shipment);
                $after = WalletTransaction::where('reference_type', 'shipment_delivery')
                    ->where('reference_id', $shipment->id)
                    ->exists();
                if (!$before && $after) {
                    $shipmentCount++;
                }
            } catch (\Throwable $e) {
                $this->warn("Shipment {$shipment->id}: " . $e->getMessage());
            }
        }

        // Legacy whole-order credits (orders without shipment rows)
        $alreadyCreditedOrderIds = WalletTransaction::whereIn('reference_type', ['order_delivery', 'shipment_delivery'])
            ->pluck('reference_id')
            ->unique()
            ->toArray();

        $orders = Order::whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('delivered_at')
            ->whereDoesntHave('shipments')
            ->whereNotIn('id', $alreadyCreditedOrderIds ?: [0])
            ->limit(100)
            ->get();

        $orderCount = 0;
        foreach ($orders as $order) {
            try {
                PayoutService::creditSellersForDeliveredOrderIfReleased($order);
                $orderCount++;
            } catch (\Throwable $e) {
                $this->warn("Order {$order->order_number}: " . $e->getMessage());
            }
        }

        $this->info("Released payouts for {$shipmentCount} shipment(s) and {$orderCount} legacy order(s).");
        return 0;
    }
}