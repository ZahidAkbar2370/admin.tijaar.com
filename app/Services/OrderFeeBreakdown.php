<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Customer checkout fees (added to order total) vs seller-side deductions (from seller earnings).
 */
class OrderFeeBreakdown
{
    public static function customerFees(Order $order): array
    {
        $marketplace = round((float) ($order->marketplace_fee ?? 0), 2);
        $online = round((float) ($order->online_transaction_fee ?? 0), 2);

        return [
            'marketplace_fee' => $marketplace,
            'marketplace_fee_type' => $order->marketplace_fee_type,
            'marketplace_fee_rate' => $order->marketplace_fee_rate !== null ? (float) $order->marketplace_fee_rate : null,
            'online_transaction_fee' => $online,
            'online_transaction_fee_type' => $order->online_transaction_fee_type,
            'online_transaction_fee_rate' => $order->online_transaction_fee_rate !== null ? (float) $order->online_transaction_fee_rate : null,
            'total' => round($marketplace + $online, 2),
        ];
    }

    /** Seller-side fees for a subset of items (seller panel) or all items (admin). */
    public static function sellerFeesFromItems(Collection $items, ?Order $order = null): array
    {
        $marketplace = round($items->sum(fn ($i) => (float) ($i->marketplace_fee_allocated ?? 0)), 2);
        $online = round($items->sum(fn ($i) => (float) ($i->online_transaction_fee_allocated ?? 0)), 2);
        $commission = round($items->sum(fn ($i) => (float) ($i->commission_amount ?? 0)), 2);

        $isFullOrder = false;
        if ($order !== null) {
            $allCount = $order->relationLoaded('items') ? $order->items->count() : $order->items()->count();
            $isFullOrder = $items->count() === $allCount && $allCount > 0;
        }
        if ($isFullOrder) {
            $persistedMp = (float) ($order->seller_marketplace_fee_total ?? 0);
            $persistedOt = (float) ($order->seller_online_transaction_fee_total ?? 0);
            if ($persistedMp > 0) {
                $marketplace = round($persistedMp, 2);
            }
            if ($persistedOt > 0) {
                $online = round($persistedOt, 2);
            }
            if ($order->seller_commission_total !== null && (float) $order->seller_commission_total > 0) {
                $commission = round((float) $order->seller_commission_total, 2);
            }
        }

        return [
            'marketplace_fee' => $marketplace,
            'marketplace_fee_type' => $order?->seller_marketplace_fee_type,
            'marketplace_fee_rate' => $order?->seller_marketplace_fee_rate !== null ? (float) $order->seller_marketplace_fee_rate : null,
            'online_transaction_fee' => $online,
            'online_transaction_fee_type' => $order?->seller_online_transaction_fee_type,
            'online_transaction_fee_rate' => $order?->seller_online_transaction_fee_rate !== null ? (float) $order->seller_online_transaction_fee_rate : null,
            'order_commission' => $commission,
            'order_commission_type' => $order?->seller_commission_type,
            'order_commission_rate' => $order?->seller_commission_rate !== null ? (float) $order->seller_commission_rate : null,
            'total' => round($marketplace + $online + $commission, 2),
        ];
    }

    public static function sellerNet(Collection $items, ?Order $order = null): float
    {
        $subtotal = round($items->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2);
        $discount = round($items->sum(fn ($i) => (float) ($i->discount_allocated ?? 0)), 2);
        $fees = self::sellerFeesFromItems($items, $order);

        return max(0, round($subtotal - $discount - $fees['total'], 2));
    }

    /** Persist seller fee rate snapshots on the order at checkout. */
    public static function snapshotSellerFeeMeta(Order $order, bool $hasPrivateItems, bool $hasBusinessItems): void
    {
        if ($hasPrivateItems) {
            $order->seller_marketplace_fee_type = MarketplaceFeeService::privateSellerMarketplaceFeeType();
            $order->seller_marketplace_fee_rate = MarketplaceFeeService::privateSellerMarketplaceFeeValue();
            $order->seller_online_transaction_fee_type = MarketplaceFeeService::privateSellerOnlineTransactionFeeType();
            $order->seller_online_transaction_fee_rate = MarketplaceFeeService::privateSellerOnlineTransactionFeeValue();
        }

        if ($hasPrivateItems && ! $hasBusinessItems) {
            $order->seller_commission_type = MarketplaceFeeService::privateSellerCommissionType();
            $order->seller_commission_rate = MarketplaceFeeService::privateSellerCommissionValue();
        } elseif ($hasBusinessItems && ! $hasPrivateItems) {
            $order->seller_commission_type = MarketplaceFeeService::sellerCommissionType();
            $order->seller_commission_rate = MarketplaceFeeService::sellerCommissionValue();
        } else {
            $order->seller_commission_type = null;
            $order->seller_commission_rate = null;
        }
    }

    public static function attachCustomerView(Order $order): void
    {
        $order->setAttribute('customer_fees', self::customerFees($order));
    }

    public static function attachSellerView(Order $order, Collection $sellerItems): void
    {
        $fees = self::sellerFeesFromItems($sellerItems, $order);
        $order->setAttribute('seller_fees', $fees);
        $order->seller_commission = $fees['order_commission'];
        $order->seller_net = self::sellerNet($sellerItems, $order);
        $order->seller_you_receive = $order->seller_net;
    }
}
