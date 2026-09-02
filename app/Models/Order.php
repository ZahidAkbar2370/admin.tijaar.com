<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'checkout_batch_id',
        'user_id',
        'status',
        'delivered_at',
        'market',
        'shipping_address_id',
        'shipping_method',
        'shipping_cost',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'marketplace_fee',
        'online_transaction_fee',
        'seller_commission_total',
        'seller_marketplace_fee_total',
        'seller_online_transaction_fee_total',
        'seller_marketplace_fee_type',
        'seller_marketplace_fee_rate',
        'seller_online_transaction_fee_type',
        'seller_online_transaction_fee_rate',
        'seller_commission_type',
        'seller_commission_rate',
        'platform_revenue',
        'marketplace_fee_type',
        'marketplace_fee_rate',
        'online_transaction_fee_type',
        'online_transaction_fee_rate',
        'coupon_id',
        'total',
        'online_amount',
        'cod_amount',
        'partial_payment_percent',
        'payment_method',
        'payment_status',
        'customer_notes',
        'rejection_reason',
        'cancellation_reason',
        'cancellation_requested_at',
        'seller_approved_at',
        'tracking_number',
        'tracking_url',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'cancellation_requested_at' => 'datetime',
        'seller_approved_at' => 'datetime',
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'marketplace_fee' => 'decimal:2',
        'online_transaction_fee' => 'decimal:2',
        'seller_commission_total' => 'decimal:2',
        'seller_marketplace_fee_total' => 'decimal:2',
        'seller_online_transaction_fee_total' => 'decimal:2',
        'seller_marketplace_fee_rate' => 'decimal:2',
        'seller_online_transaction_fee_rate' => 'decimal:2',
        'seller_commission_rate' => 'decimal:2',
        'platform_revenue' => 'decimal:2',
        'marketplace_fee_rate' => 'decimal:2',
        'online_transaction_fee_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'online_amount' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'partial_payment_percent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->with('product');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(OrderTimeline::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * Effective order status derived from per-seller fulfillment + shipments.
     * Rejected seller portions are ignored for shipping progress.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if (in_array($this->status, ['cancelled', 'refunded', 'cancellation_requested', 'pending'], true)) {
            return $this->status === 'paid' ? 'processing' : $this->status;
        }

        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $shipments = $this->relationLoaded('shipments') ? $this->shipments : $this->shipments()->get();
        $priced = $items->filter(fn ($i) => (float) $i->price > 0);
        $active = $priced->filter(fn ($i) => ! in_array($i->fulfillment_status ?? '', ['rejected', 'cancelled'], true));

        if ($priced->isNotEmpty() && $active->isEmpty()) {
            return in_array($this->status, ['refunded', 'cancelled'], true) ? $this->status : 'cancelled';
        }

        $sellerStores = $active->pluck('store_id')->filter()->unique()->values();
        $sellerPrivateIds = $active->where('seller_type', 'private')->pluck('seller_id')->filter()->unique()->values();
        // Also include store-less non-private sellers
        $sellerLooseIds = $active->filter(fn ($i) => empty($i->store_id) && $i->seller_type !== 'private')
            ->pluck('seller_id')->filter()->unique()->values();

        $hasMultipleSellers = ($sellerStores->count() + $sellerPrivateIds->count() + $sellerLooseIds->count()) > 1;

        if (!$hasMultipleSellers && $shipments->isEmpty()) {
            if ($this->status === 'paid') {
                return 'processing';
            }
            // Prefer item fulfillment when present
            $firstFs = $active->first()?->fulfillment_status;
            if (in_array($firstFs, ['approved', 'processing', 'pending'], true)) {
                return $firstFs === 'pending' ? 'processing' : $firstFs;
            }
            return in_array($this->status, [
                'pending', 'processing', 'approved', 'shipped', 'delivered', 'completed',
                'cancelled', 'refunded', 'cancellation_requested',
            ], true) ? $this->status : 'pending';
        }

        $allSellersDelivered = true;
        $anySellerShipped = false;
        $anyProcessing = false;
        $anyApproved = false;

        $checkPortion = function ($shipment, $portionItems) use (&$allSellersDelivered, &$anySellerShipped, &$anyProcessing, &$anyApproved) {
            $ps = \App\Services\SellerFulfillmentService::portionStatus($portionItems, $shipment);
            if ($ps === 'delivered') {
                return;
            }
            $allSellersDelivered = false;
            if (in_array($ps, ['shipped'], true)) {
                $anySellerShipped = true;
            } elseif ($ps === 'approved') {
                $anyApproved = true;
            } else {
                $anyProcessing = true;
            }
        };

        foreach ($sellerStores as $storeId) {
            $s = $shipments->firstWhere('store_id', $storeId);
            $portionItems = $active->where('store_id', $storeId);
            $checkPortion($s, $portionItems);
        }
        foreach ($sellerPrivateIds->merge($sellerLooseIds)->unique() as $sid) {
            $s = $shipments->first(fn ($sh) => (int) $sh->seller_id === (int) $sid && empty($sh->store_id));
            $portionItems = $active->filter(fn ($i) => empty($i->store_id) && (int) $i->seller_id === (int) $sid);
            $checkPortion($s, $portionItems);
        }

        if ($allSellersDelivered && $active->isNotEmpty()) {
            return 'completed';
        }
        if ($anySellerShipped) {
            return 'shipped';
        }
        if ($anyProcessing) {
            return 'processing';
        }
        if ($anyApproved) {
            return 'approved';
        }

        if ($this->status === 'paid') {
            return 'processing';
        }
        return in_array($this->status, [
            'pending', 'processing', 'approved', 'shipped', 'delivered', 'completed',
            'cancelled', 'refunded', 'cancellation_requested',
        ], true) ? $this->status : 'processing';
    }

    /**
     * Whether order has an open return/refund request or dispute that should block further shipping.
     */
    public function hasOpenReturnOrDispute(): bool
    {
        if ($this->disputes()->whereIn('status', ['open', 'seller_responded'])->exists()) {
            return true;
        }
        if ($this->refunds()->where('status', 'pending')->exists()) {
            return true;
        }
        if ($this->returnRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
            return true;
        }
        return false;
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'TJR';
        $date = date('ymd');
        $rand = strtoupper(Str::random(4));
        $seq = static::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT) . $rand;
    }
}
