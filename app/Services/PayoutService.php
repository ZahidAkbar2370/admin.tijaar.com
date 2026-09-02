<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /** Order statuses that are considered "paid" for display (e.g. order list). */
    public const PAYABLE_STATUSES = ['paid', 'processing', 'shipped', 'delivered', 'completed'];

    /** Only delivered/completed orders count for earnings (COD = payment on delivery; prepaid = release after delivery). */
    public const EARNINGS_ORDER_STATUSES = ['delivered', 'completed'];

    /**
     * Per-user override, else the private-seller (customer) default when set, else global payout_hold_days.
     */
    public static function holdDaysForUser(?User $user, ?string $sellerType = null): int
    {
        if ($user && $user->payout_hold_days !== null) {
            return max(0, (int) $user->payout_hold_days);
        }

        if ($sellerType === 'private') {
            $privateHold = Setting::get('private_payout_hold_days', '');
            if ($privateHold !== null && $privateHold !== '') {
                return max(0, (int) $privateHold);
            }
        }

        return max(0, (int) (Setting::get('payout_hold_days', 0) ?: 0));
    }

    public static function getEarningsForUser(User $user, string $sellerType = 'business'): array
    {
        $holdDays = self::holdDaysForUser($user, $sellerType);

        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', self::EARNINGS_ORDER_STATUSES)
            ->where('orders.payment_status', '!=', 'refunded');

        // Holding period: only include earnings when delivered_at + hold_days is in the past (or hold_days is 0)
        if ($holdDays > 0) {
            $query->whereNotNull('orders.delivered_at')
                ->where('orders.delivered_at', '<=', now()->subDays($holdDays));
        }

        if ($sellerType === 'business') {
            $store = $user->seller?->store;
            if (!$store) {
                return ['total' => 0, 'commission' => 0, 'net' => 0, 'items' => [], 'already_paid' => 0];
            }
            $query->where('order_items.store_id', $store->id);
        } else {
            $query->where('order_items.seller_id', $user->id)
                ->where('order_items.seller_type', 'private');
        }

        $items = $query->select(
            'order_items.id',
            'order_items.order_id',
            'order_items.product_name',
            'order_items.quantity',
            'order_items.price',
            'order_items.commission_amount',
            'order_items.marketplace_fee_allocated',
            'order_items.online_transaction_fee_allocated',
            'order_items.discount_allocated'
        )->get();

        // Sum allocated net_amount per order_item (supports partial allocations across multiple payouts)
        $allocatedMap = [];
        foreach (PayoutItem::whereIn('order_item_id', $items->pluck('id'))->get() as $pi) {
            $allocatedMap[$pi->order_item_id] = ($allocatedMap[$pi->order_item_id] ?? 0) + (float) $pi->net_amount;
        }

        $grossSubtotal = 0;
        $discountTotal = 0;
        $total = 0;
        $commission = 0;
        $netTotal = 0;
        $breakdown = [];

        foreach ($items as $item) {
            $subtotal = (float) $item->quantity * (float) $item->price;
            $discountAlloc = (float) ($item->discount_allocated ?? 0);
            $effectiveSubtotal = max(0, $subtotal - $discountAlloc);
            $comm = (float) ($item->commission_amount ?? 0);
            $marketplaceAlloc = (float) ($item->marketplace_fee_allocated ?? 0);
            $onlineAlloc = (float) ($item->online_transaction_fee_allocated ?? 0);
            $itemNet = \App\Services\MarketplaceFeeService::sellerLineNet(
                (float) $item->quantity,
                (float) $item->price,
                $discountAlloc,
                $comm,
                $marketplaceAlloc,
                $onlineAlloc
            );
            $allocated = $allocatedMap[$item->id] ?? 0;
            $remaining = round($itemNet - $allocated, 2);
            if ($remaining <= 0) {
                continue;
            }
            if ($itemNet > 0) {
                $ratio = $remaining / $itemNet;
                $grossSubtotal += $subtotal * $ratio;
                $discountTotal += $discountAlloc * $ratio;
                $total += $effectiveSubtotal * $ratio;
                $commission += $comm * $ratio;
            }
            $netTotal += $remaining;
            $breakdown[] = [
                'order_item_id' => $item->id,
                'order_id' => $item->order_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $subtotal,
                'discount_allocated' => $discountAlloc,
                'effective_subtotal' => round($effectiveSubtotal, 2),
                'commission_amount' => $comm,
                'marketplace_fee_allocated' => $marketplaceAlloc,
                'online_transaction_fee_allocated' => $onlineAlloc,
                'net_amount' => $remaining,
                'item_net_full' => $itemNet,
            ];
        }

        $alreadyPaid = Payout::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        return [
            'gross_subtotal' => round($grossSubtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total' => round($total, 2),
            'commission' => round($commission, 2),
            'net' => round($netTotal, 2),
            'items' => $breakdown,
            'already_paid' => (float) $alreadyPaid,
        ];
    }

    public static function getMinPayoutThreshold(string $sellerType = 'business'): float
    {
        $key = $sellerType === 'private' ? 'private_payout_threshold' : 'payout_min_threshold';
        $default = $sellerType === 'private' ? 500 : 1000;
        return (float) (Setting::get($key) ?: $default);
    }

    /**
     * @param  float|null  $requestedAmount  Amount to request; null = request full available balance
     */
    public static function createPayoutRequest(User $user, string $sellerType, string $method = 'bank', ?float $requestedAmount = null): Payout
    {
        $earnings = self::getEarningsForUser($user, $sellerType);
        $min = self::getMinPayoutThreshold($sellerType);
        $availableNet = (float) $earnings['net'];

        if ($availableNet < $min) {
            throw new \InvalidArgumentException("Minimum payout is " . number_format($min, 2) . ". Available: " . number_format($availableNet, 2));
        }

        if (empty($earnings['items'])) {
            throw new \InvalidArgumentException('No earnings available for payout.');
        }

        $amount = $requestedAmount !== null && $requestedAmount > 0
            ? round((float) $requestedAmount, 2)
            : $availableNet;

        if ($amount < $min) {
            throw new \InvalidArgumentException("Minimum payout is " . number_format($min, 2) . ". You requested: " . number_format($amount, 2));
        }
        if ($amount > $availableNet) {
            throw new \InvalidArgumentException("Requested amount cannot exceed available balance. Available: " . number_format($availableNet, 2));
        }

        return DB::transaction(function () use ($user, $sellerType, $method, $earnings, $amount) {
            $payout = Payout::create([
                'payout_number' => Payout::generatePayoutNumber(),
                'user_id' => $user->id,
                'seller_type' => $sellerType,
                'amount' => $amount,
                'status' => 'pending',
                'method' => $method,
                'bank_account_holder' => $user->seller?->bank_account_holder,
                'bank_account_number' => $user->seller?->bank_account_number,
                'bank_name' => $user->seller?->bank_name,
            ]);

            $remainingToAllocate = $amount;
            foreach ($earnings['items'] as $item) {
                if ($remainingToAllocate <= 0) {
                    break;
                }
                $itemNet = (float) $item['net_amount'];
                $take = round(min($itemNet, $remainingToAllocate), 2);
                if ($take <= 0) {
                    continue;
                }
                $ratio = $itemNet > 0 ? $take / $itemNet : 0;
                $allocSubtotal = round((float) $item['subtotal'] * $ratio, 2);
                $allocCommission = round((float) $item['commission_amount'] * $ratio, 2);
                PayoutItem::create([
                    'payout_id' => $payout->id,
                    'order_item_id' => $item['order_item_id'],
                    'order_item_subtotal' => $allocSubtotal,
                    'commission_amount' => $allocCommission,
                    'net_amount' => $take,
                ]);
                $remainingToAllocate -= $take;
            }

            // Debit wallet immediately when payout is requested so "Current Balance" shows 0 and transaction history is clear
            self::debitSellerWalletForPayout($payout);

            return $payout;
        });
    }

    public static function approvePayout(Payout $payout, ?int $approvedBy = null): void
    {
        $payout->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public static function rejectPayout(Payout $payout, string $reason, ?int $approvedBy = null): void
    {
        $payout->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public static function markPaid(Payout $payout): void
    {
        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Credit sellers for delivered order only if holding period has passed (or hold is 0).
     * Otherwise credit is done by ReleaseHeldPayoutsCommand after hold period.
     */
    public static function creditSellersForDeliveredOrderIfReleased(Order $order): void
    {
        if (!in_array($order->status, ['delivered', 'completed'])) {
            return;
        }
        // Use buyer-agnostic global hold here; per-seller hold applied in creditSellerForDeliveredShipment
        $holdDays = max(0, (int) (Setting::get('payout_hold_days', 0) ?: 0));
        if ($holdDays > 0 && $order->delivered_at) {
            $releaseAt = $order->delivered_at->copy()->addDays($holdDays);
            if (now()->lt($releaseAt)) {
                return; // Still in holding period
            }
        }
        self::creditSellersForDeliveredOrder($order);
    }

    /**
     * Credit one seller when their shipment portion is delivered (multi-seller safe).
     * Idempotent per shipment via reference_type=shipment_delivery.
     */
    public static function creditSellerForDeliveredShipment(\App\Models\Shipment $shipment): void
    {
        $shipment->loadMissing(['order.items', 'store.seller']);
        $order = $shipment->order;
        if (!$order || $shipment->status !== 'delivered') {
            return;
        }

        $already = WalletTransaction::where('reference_type', 'shipment_delivery')
            ->where('reference_id', $shipment->id)
            ->exists();
        if ($already) {
            return;
        }

        $items = $order->items->filter(function ($item) use ($shipment) {
            if ($shipment->store_id) {
                return (int) $item->store_id === (int) $shipment->store_id;
            }
            return (int) $item->seller_id === (int) $shipment->seller_id && !$item->store_id;
        });

        $net = 0.0;
        $userId = null;
        if ($shipment->store_id) {
            $userId = $shipment->store?->seller?->user_id
                ?? Store::with('seller')->find($shipment->store_id)?->seller?->user_id;
        } elseif ($shipment->seller_id) {
            $userId = (int) $shipment->seller_id;
        }

        foreach ($items as $item) {
            $subtotal = (float) $item->quantity * (float) $item->price;
            $discountAlloc = (float) ($item->discount_allocated ?? 0);
            $effectiveSubtotal = max(0, $subtotal - $discountAlloc);
            $commission = (float) ($item->commission_amount ?? 0);
            $marketplaceAlloc = (float) ($item->marketplace_fee_allocated ?? 0);
            $onlineAlloc = (float) ($item->online_transaction_fee_allocated ?? 0);
            $net += MarketplaceFeeService::sellerLineNet(
                (float) $item->quantity,
                (float) $item->price,
                $discountAlloc,
                $commission,
                $marketplaceAlloc,
                $onlineAlloc
            );
            if (!$userId) {
                if ($item->store_id) {
                    $userId = Store::with('seller')->find($item->store_id)?->seller?->user_id;
                } elseif ($item->seller_id) {
                    $userId = (int) $item->seller_id;
                }
            }
        }

        if (!$userId || $net <= 0) {
            return;
        }

        $sellerUser = User::find($userId);
        $holdDays = self::holdDaysForUser($sellerUser, $shipment->store_id ? 'business' : 'private');
        if ($holdDays > 0) {
            $deliveredAt = $shipment->delivered_at ?? now();
            if ($deliveredAt->copy()->addDays($holdDays)->isFuture()) {
                return; // release-held command will retry
            }
        }

        $currency = $order->market === 'AE' ? 'AED' : 'PKR';
        $wallet = Wallet::getOrCreateForUser($userId, $currency);
        $newBalance = (float) $wallet->balance + $net;
        $wallet->update(['balance' => $newBalance]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'order_payment',
            'amount' => $net,
            'balance_after' => $newBalance,
            'reference_type' => 'shipment_delivery',
            'reference_id' => $shipment->id,
            'description' => 'Order Earnings — #' . $order->order_number
                . ($shipment->store?->name ? ' (' . $shipment->store->name . ')' : ''),
        ]);
    }

    /**
     * When order is fully delivered, credit any sellers not yet paid via shipment_delivery
     * (legacy fallback for orders without per-shipment credits).
     */
    public static function creditSellersForDeliveredOrder(Order $order): void
    {
        if (!in_array($order->status, ['delivered', 'completed'], true)
            && !in_array($order->effective_status ?? '', ['delivered', 'completed'], true)) {
            return;
        }

        $order->load(['items', 'shipments.store.seller']);

        // Prefer per-shipment credits when shipments exist
        $shipments = $order->shipments->where('status', 'delivered');
        if ($shipments->isNotEmpty()) {
            foreach ($shipments as $shipment) {
                self::creditSellerForDeliveredShipment($shipment);
            }

            return;
        }

        $alreadyCredited = WalletTransaction::where('reference_type', 'order_delivery')
            ->where('reference_id', $order->id)
            ->exists();
        if ($alreadyCredited) {
            return;
        }

        $sellerNet = [];
        foreach ($order->items as $item) {
            $subtotal = (float) $item->quantity * (float) $item->price;
            $discountAlloc = (float) ($item->discount_allocated ?? 0);
            $effectiveSubtotal = max(0, $subtotal - $discountAlloc);
            $commission = (float) ($item->commission_amount ?? 0);
            $net = MarketplaceFeeService::sellerLineNet(
                (float) $item->quantity,
                (float) $item->price,
                $discountAlloc,
                $commission,
                (float) ($item->marketplace_fee_allocated ?? 0),
                (float) ($item->online_transaction_fee_allocated ?? 0)
            );
            $userId = null;
            if ($item->store_id) {
                $store = Store::find($item->store_id);
                $userId = $store?->seller?->user_id;
            } elseif ($item->seller_id) {
                $userId = $item->seller_id;
            }
            if ($userId && $net > 0) {
                $sellerNet[$userId] = ($sellerNet[$userId] ?? 0) + $net;
            }
        }

        $currency = $order->market === 'AE' ? 'AED' : 'PKR';
        foreach ($sellerNet as $userId => $amount) {
            $wallet = Wallet::getOrCreateForUser($userId, $currency);
            $newBalance = (float) $wallet->balance + $amount;
            $wallet->update(['balance' => $newBalance]);
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'order_payment',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => 'order_delivery',
                'reference_id' => $order->id,
                'description' => 'Order Earnings — #' . $order->order_number,
            ]);
        }
    }

    /**
     * Debit seller wallet for a payout. Called when payout is requested (so balance shows 0 immediately)
     * and optionally again on approve for legacy payouts. Idempotent: skips if already debited for this payout.
     *
     * @param string|null $description Defaults to "Payout #X requested" when omitted (request flow); use "Payout #X approved" for legacy approve flow.
     */
    public static function debitSellerWalletForPayout(Payout $payout, ?string $description = null): void
    {
        $exists = WalletTransaction::where('reference_type', 'payout')
            ->where('reference_id', $payout->id)
            ->exists();
        if ($exists) {
            return;
        }

        $wallet = Wallet::getOrCreateForUser($payout->user_id);
        $amount = (float) $payout->amount;
        $newBalance = (float) $wallet->balance - $amount;
        if ($newBalance < 0) {
            throw new \RuntimeException('Insufficient wallet balance for payout.');
        }
        $wallet->update(['balance' => $newBalance]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'payout',
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'reference_type' => 'payout',
            'reference_id' => $payout->id,
            'description' => $description ?? ('Payout Requested — #' . $payout->payout_number),
        ]);
    }

    /**
     * When a requested payout is rejected, credit the wallet back if it was debited at request time.
     */
    public static function creditWalletBackForRejectedPayout(Payout $payout): void
    {
        $debit = WalletTransaction::where('reference_type', 'payout')
            ->where('reference_id', $payout->id)
            ->where('amount', '<', 0)
            ->first();
        if (!$debit) {
            return;
        }
        $wallet = Wallet::find($debit->wallet_id);
        if (!$wallet) {
            return;
        }
        $creditAmount = abs((float) $debit->amount);
        $newBalance = (float) $wallet->balance + $creditAmount;
        $wallet->update(['balance' => $newBalance]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'payout_refund',
            'amount' => $creditAmount,
            'balance_after' => $newBalance,
            'reference_type' => 'payout',
            'reference_id' => $payout->id,
            'description' => 'Payout Returned to Wallet — #' . $payout->payout_number,
        ]);
    }

    /**
     * Credit seller wallet from available earnings (e.g. for package purchase).
     * Creates a payout with status 'wallet_credit' so those earnings are excluded from future payout requests.
     */
    public static function creditWalletFromEarnings(User $user, float $amount, string $sellerType = 'business'): float
    {
        if ($amount <= 0) {
            return 0;
        }
        $earnings = self::getEarningsForUser($user, $sellerType);
        $available = (float) ($earnings['net'] ?? 0);
        if ($available <= 0 || empty($earnings['items'])) {
            return 0;
        }
        $toCredit = min($amount, $available);
        if ($toCredit <= 0) {
            return 0;
        }

        return (float) DB::transaction(function () use ($user, $sellerType, $earnings, $toCredit) {
            $payout = Payout::create([
                'payout_number' => Payout::generatePayoutNumber(),
                'user_id' => $user->id,
                'seller_type' => $sellerType,
                'amount' => 0,
                'status' => 'wallet_credit',
                'method' => 'wallet',
            ]);

            $allocated = 0.0;
            foreach ($earnings['items'] as $item) {
                if ($allocated >= $toCredit) {
                    break;
                }
                $net = (float) ($item['net_amount'] ?? 0);
                if ($net <= 0) {
                    continue;
                }
                PayoutItem::create([
                    'payout_id' => $payout->id,
                    'order_item_id' => $item['order_item_id'],
                    'order_item_subtotal' => $item['subtotal'] ?? 0,
                    'commission_amount' => $item['commission_amount'] ?? 0,
                    'net_amount' => $net,
                ]);
                $allocated += $net;
            }
            if ($allocated <= 0) {
                return 0;
            }

            $payout->update(['amount' => $allocated]);

            $currency = $user->seller?->store ? 'PKR' : 'PKR';
            $wallet = Wallet::getOrCreateForUser($user->id, $currency);
            $newBalance = (float) $wallet->balance + $allocated;
            $wallet->update(['balance' => $newBalance]);
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'earnings_credit',
                'amount' => $allocated,
                'balance_after' => $newBalance,
                'reference_type' => 'payout',
                'reference_id' => $payout->id,
                'description' => 'Earnings Added to Wallet',
            ]);

            return $allocated;
        });
    }
}
