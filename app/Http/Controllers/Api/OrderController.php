<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Commission;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\ProductVariant;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\EasypaisaService;
use App\Services\JazzCashService;
use App\Services\OrderWorkflowService;
use App\Services\StripeService;
use App\Services\ShippingService;
use App\Services\PkCourierShippingService;
use App\Services\PartialPaymentService;
use App\Services\ActivityLogger;
use App\Services\WachatService;
use App\Support\PhoneHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role === 'seller') {
            return response()->json([
                'success' => false,
                'message' => 'Sellers cannot place orders while logged in. Please log out and purchase as a customer.',
            ], 403);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before placing an order.',
                'error_code' => 'email_verification_required',
            ], 422);
        }

        if (! $user->phone || ! $user->phone_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please add and verify your mobile number in Profile before placing an order.',
                'error_code' => 'phone_verification_required',
            ], 422);
        }

        $request->validate([
            'shipping_address_id' => ['required', Rule::exists('addresses', 'id')->where('user_id', $user->id)],
            'payment_method' => 'nullable|string|in:cod,stripe,paypal,jazzcash,jazzcash_partial,easypaisa,wallet',
            'payment_phone' => 'nullable|string|max:20',
            'payment_cnic' => 'nullable|string|max:20',
            'customer_notes' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:32',
            'shipping_rule_id' => 'nullable|exists:shipping_rules,id',
            'courier_provider' => 'nullable|string|in:'.implode(',', array_keys(\App\Support\CourierCatalog::PROVIDERS)),
        ], [
            'shipping_address_id.required' => 'Please select a shipping address.',
            'shipping_address_id.exists' => 'The selected shipping address is invalid or does not belong to your account.',
            'payment_method.in' => 'Please select a valid payment method.',
        ]);

        $address = Address::where('user_id', $user->id)->findOrFail($request->shipping_address_id);

        $normalizedPaymentPhone = PhoneHelper::normalize(
            $request->input('payment_phone') ?: $user->phone ?: $address->phone
        );

        $paymentMethodEarly = $request->payment_method ?? 'cod';
        // JazzCash phone/CNIC are collected when the customer clicks Pay Now ? not required at place-order.

        $cart = Cart::getOrCreate($user->id);
        $cart->load('items.product.store.seller', 'items.variant');

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        // Prevent customer from buying their own listings (causes disputes, returns, refunds to fail)
        foreach ($cart->items as $item) {
            $product = $item->product;
            $sellerUserId = $product->store_id && $product->store?->seller
                ? (int) $product->store->seller->user_id
                : ($product->seller_id ? (int) $product->seller_id : null);
            if ($sellerUserId && $sellerUserId === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot purchase your own listings. Please remove "' . ($product->name ?? 'your item') . '" from the cart and log in as a different customer to buy it.',
                ], 422);
            }
        }

        foreach ($cart->items as $item) {
            $product = $item->product;
            $required = $item->quantity;
            if ($item->variant_id > 0 && $item->variant) {
                if ((int) $item->variant->quantity < $required) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name} (selected variant). Only " . (int) $item->variant->quantity . " available.",
                        'code' => 'insufficient_stock',
                    ], 422);
                }
            } else {
                // Exclude this cart's own reservation ? stock was held when the item was added.
                $available = $product->getAvailableQuantity('cart', (string) $cart->id);
                if ($available < $required) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Only {$available} available.",
                        'code' => 'insufficient_stock',
                    ], 422);
                }
            }
        }

        $discountAmount = 0;
        $coupon = null;
        if ($request->filled('coupon_code')) {
            $couponResult = CouponService::validate($request->coupon_code, $cart);
            if (!$couponResult['valid']) {
                return response()->json(['success' => false, 'message' => $couponResult['message']], 422);
            }
            $discountAmount = $couponResult['discount'];
            $coupon = $couponResult['coupon'];
        }

        $subtotalPre = $cart->items->sum(fn ($i) => (float) $i->price * $i->quantity);
        $market = $cart->market ?? 'PK';
        $marketKey = $market === 'AE' ? 'AE' : 'PK';
        $country = $address->country ?? 'Pakistan';

        if ($marketKey === 'PK') {
            $cart->load('items.product.store', 'items.product.sellerUser.addresses');
            $preferredCourier = $request->input('courier_provider');
            if ($preferredCourier && !PkCourierShippingService::isValidProvider($preferredCourier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected courier is not available. Please choose another shipping option.',
                ], 422);
            }
            $shippingResult = PkCourierShippingService::calculateForItems($cart->items, $address, 'PK', $preferredCourier);
            if (!($shippingResult['rates_available'] ?? true)) {
                return response()->json([
                    'success' => false,
                    'message' => $shippingResult['unavailable_message'] ?? 'Shipping rates are unavailable for this address.',
                ], 422);
            }
            if ($preferredCourier) {
                $selectedOpt = collect($shippingResult['courier_options'] ?? [])->firstWhere('id', $preferredCourier);
                if (!$selectedOpt || !($selectedOpt['rates_available'] ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => $selectedOpt['unavailable_message'] ?? 'Selected courier rates are unavailable for this address.',
                    ], 422);
                }
                $shippingResult['cost'] = (float) $selectedOpt['cost'];
                $shippingResult['total_cost'] = (float) $selectedOpt['total_cost'];
                $shippingResult['stores'] = $selectedOpt['stores'] ?? [];
                $shippingResult['carrier'] = $selectedOpt['carrier'];
                $shippingResult['selected_courier'] = $preferredCourier;
            }
            $shippingCost = (float) ($shippingResult['total_cost'] ?? $shippingResult['cost'] ?? 0);
        } elseif ($request->filled('shipping_rule_id')) {
            $ruleCost = ShippingService::costForRule(
                (int) $request->shipping_rule_id,
                $subtotalPre,
                0,
                $country,
                $marketKey
            );
            $shippingCost = $ruleCost !== null ? (float) $ruleCost : 0;
        } else {
            $shippingResult = ShippingService::calculate($subtotalPre, 0, $country, $marketKey);
            $shippingCost = (float) ($shippingResult['cost'] ?? 0);
        }

        if (!isset($shippingResult)) {
            $shippingResult = ['stores' => []];
        }

        $paymentMethod = $request->payment_method ?? 'cod';
        if ($paymentMethod === 'jazzcash_partial' && !PartialPaymentService::isPartialEnabled()) {
            return response()->json(['success' => false, 'message' => 'Partial JazzCash payment is not available'], 422);
        }
        if (!self::isPaymentMethodEnabled($paymentMethod)) {
            return response()->json(['success' => false, 'message' => 'Selected payment method is not available'], 422);
        }

        $feeBreakdown = \App\Services\MarketplaceFeeService::customerTotal(
            $subtotalPre,
            $shippingCost,
            $discountAmount,
            $paymentMethod
        );
        $marketplaceFee = (float) $feeBreakdown['marketplace_fee'];
        $onlineTransactionFee = (float) $feeBreakdown['online_transaction_fee'];
        $orderTotalPre = (float) $feeBreakdown['total'];

        if ($request->payment_method === 'wallet') {
            $wallet = Wallet::getOrCreateForUser($user->id, $market === 'AE' ? 'AED' : 'PKR');
            if ((float) $wallet->balance < $orderTotalPre) {
                return response()->json(['success' => false, 'message' => 'Insufficient wallet balance'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $order = new Order();
            $order->order_number = Order::generateOrderNumber();
            $order->user_id = $user->id;
            $order->status = 'pending';
            $order->market = $cart->market ?? 'PK';
            $order->shipping_address_id = $address->id;
            $order->payment_method = $paymentMethod;
            // Online + COD start unpaid; wallet is marked paid after debit below.
            $order->payment_status = 'pending';
            $order->customer_notes = $request->customer_notes;
            $order->subtotal = 0;
            $order->tax_amount = 0;
            $order->discount_amount = $discountAmount;
            $order->marketplace_fee = $marketplaceFee;
            $order->online_transaction_fee = $onlineTransactionFee;
            $order->marketplace_fee_type = $feeBreakdown['marketplace_fee_type'];
            $order->marketplace_fee_rate = $feeBreakdown['marketplace_fee_value'];
            $order->online_transaction_fee_type = $feeBreakdown['online_transaction_fee_type'];
            $order->online_transaction_fee_rate = $feeBreakdown['online_transaction_fee_value'];
            $order->seller_commission_total = 0;
            $order->platform_revenue = 0;
            $order->coupon_id = $coupon?->id;
            $order->shipping_cost = $shippingCost;
            if ($marketKey === 'PK') {
                $order->shipping_method = $shippingResult['selected_courier']
                    ?? PkCourierShippingService::activeProvider();
            }
            $order->total = 0;
            $order->save();

            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'note' => 'Order created',
            ]);

            $subtotal = 0;
            $itemSubtotals = [];
            foreach ($cart->items as $item) {
                $product = $item->product;
                $itemSubtotal = (float) $item->price * $item->quantity;
                $subtotal += $itemSubtotal;
                $itemSubtotals[] = ['item' => $item, 'product' => $product, 'item_subtotal' => $itemSubtotal];
            }

            $order->subtotal = $subtotal;
            $order->total = max(0, round(
                max(0, $subtotal - (float) $order->discount_amount)
                + $shippingCost
                + $marketplaceFee
                + $onlineTransactionFee,
                2
            ));

            $split = PartialPaymentService::split(
                $paymentMethod,
                $subtotal,
                $shippingCost,
                (float) $order->discount_amount,
                $marketplaceFee,
                $onlineTransactionFee
            );
            $order->online_amount = $split['online_amount'];
            $order->cod_amount = $split['cod_amount'];
            $order->partial_payment_percent = $split['partial_payment_percent'];
            $order->save();

            $commissionTotal = 0;
            $sellerMarketplaceTotal = 0;
            $sellerOnlineTotal = 0;
            $hasPrivateItems = false;
            $hasBusinessItems = false;
            foreach ($itemSubtotals as $row) {
                $item = $row['item'];
                $product = $row['product'];
                $itemSubtotal = $row['item_subtotal'];
                $storeId = $product->store_id;
                $sellerId = $product->seller_id;
                $sellerType = $product->seller_type ?? 'business';
                if ($sellerType === 'private') {
                    $hasPrivateItems = true;
                } else {
                    $hasBusinessItems = true;
                }
                $categoryId = $product->category_id;

                $discountAllocated = $subtotal > 0 ? round($itemSubtotal / $subtotal * $discountAmount, 2) : 0;
                $effectiveSubtotal = max(0, $itemSubtotal - $discountAllocated);
                $commission = Commission::calculateFor($effectiveSubtotal, $storeId, $categoryId, $sellerType);
                $commissionTotal += $commission;

                $marketplaceFeeAllocated = 0.0;
                $onlineFeeAllocated = 0.0;
                if ($sellerType === 'private') {
                    $deductions = \App\Services\MarketplaceFeeService::privateSellerLineDeductions(
                        $effectiveSubtotal,
                        $paymentMethod
                    );
                    $marketplaceFeeAllocated = (float) $deductions['marketplace_fee_allocated'];
                    $onlineFeeAllocated = (float) $deductions['online_transaction_fee_allocated'];
                    $sellerMarketplaceTotal += $marketplaceFeeAllocated;
                    $sellerOnlineTotal += $onlineFeeAllocated;
                }

                $orderItemOptions = $item->options ?? [];
                if ($item->variant_id > 0 && $item->variant) {
                    $orderItemOptions['variant_id'] = $item->variant_id;
                    $orderItemOptions['variant_attributes'] = $item->variant->attributes ?? [];
                    $item->variant->decrement('quantity', $item->quantity);
                } else {
                    $product->decrement('quantity', $item->quantity);
                }

                $product->refresh();
                \App\Services\StockAlertService::syncForProduct($product);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'seller_id' => $sellerId,
                    'product_name' => $product->name,
                    'product_sku' => $item->variant_id > 0 && $item->variant ? $item->variant->sku : $product->sku,
                    'product_image_path' => OrderItem::snapshotImagePath(
                        $product,
                        $item->variant_id > 0 && $item->variant ? $item->variant : null
                    ),
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'commission_amount' => $commission,
                    'marketplace_fee_allocated' => $marketplaceFeeAllocated,
                    'online_transaction_fee_allocated' => $onlineFeeAllocated,
                    'discount_allocated' => $discountAllocated,
                    'seller_type' => $sellerType,
                    'fulfillment_status' => 'pending',
                    'options' => !empty($orderItemOptions) ? $orderItemOptions : null,
                ]);
            }

            $order->seller_commission_total = round($commissionTotal, 2);
            $order->seller_marketplace_fee_total = round($sellerMarketplaceTotal, 2);
            $order->seller_online_transaction_fee_total = round($sellerOnlineTotal, 2);
            \App\Services\OrderFeeBreakdown::snapshotSellerFeeMeta($order, $hasPrivateItems, $hasBusinessItems);
            $sellerDeductionsTotal = $sellerMarketplaceTotal + $sellerOnlineTotal;
            $order->platform_revenue = round(
                $marketplaceFee + $onlineTransactionFee + $commissionTotal + $sellerDeductionsTotal,
                2
            );
            $order->save();

            if ($coupon) {
                CouponService::incrementUsage($coupon);
            }

            $paymentMethod = $order->payment_method ?? 'cod';
            $currency = $order->market === 'AE' ? 'AED' : 'PKR';

            $payment = null;
            $paymentMeta = array_filter([
                'payment_phone' => $normalizedPaymentPhone,
                'payment_cnic' => $request->input('payment_cnic'),
            ], fn ($v) => $v !== null && $v !== '');

            if ($paymentMethod === 'jazzcash_partial') {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'jazzcash',
                    'amount' => $split['online_amount'],
                    'currency' => $currency,
                    'status' => 'pending',
                    'gateway_response' => $paymentMeta ?: null,
                ]);
                Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'cod',
                    'amount' => $split['cod_amount'],
                    'currency' => $currency,
                    'status' => 'pending',
                ]);
            } elseif ($paymentMethod === 'jazzcash') {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'jazzcash',
                    'amount' => $split['online_amount'],
                    'currency' => $currency,
                    'status' => 'pending',
                    'gateway_response' => $paymentMeta ?: null,
                ]);
            } elseif ($paymentMethod === 'cod') {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'cod',
                    'amount' => $split['cod_amount'],
                    'currency' => $currency,
                    'status' => 'pending',
                ]);
            } else {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'gateway' => $paymentMethod,
                    'amount' => $order->total,
                    'currency' => $currency,
                    'status' => 'pending',
                    'gateway_response' => $paymentMeta ?: null,
                ]);
            }

            if ($paymentMethod === 'cod') {
                // Task 20: place as Pending. COD has no online payment; sellers still see it (sellerVisibleScope).
                $order->update(['payment_status' => 'pending', 'status' => 'pending']);
                OrderTimeline::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'note' => 'COD order placed ? awaiting seller approval',
                ]);
                // Move to Processing so seller approve flow matches paid orders (no gateway wait).
                $order->update(['status' => 'processing']);
                OrderTimeline::create([
                    'order_id' => $order->id,
                    'status' => 'processing',
                    'note' => 'COD order ready for seller approval',
                ]);
                \App\Services\SellerFulfillmentService::markItemsProcessing($order->fresh());
            } elseif ($paymentMethod === 'wallet') {
                $wallet = Wallet::getOrCreateForUser($user->id, $currency === 'AED' ? 'AED' : 'PKR');
                $newBalance = (float) $wallet->balance - (float) $order->total;
                $wallet->update(['balance' => $newBalance]);
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'order_payment',
                    'amount' => -(float) $order->total,
                    'balance_after' => $newBalance,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'description' => 'Order Payment — ' . $order->order_number,
                    'meta' => ['order_id' => $order->id],
                ]);
                $payment->update(['status' => 'completed', 'paid_at' => now()]);
                OrderWorkflowService::markPaymentSuccess($order->fresh(), 'Payment completed via wallet');
            }
            // stripe / paypal / jazzcash / easypaisa stay pending until gateway success

            $cart->items()->delete();
            \App\Models\ReservedStock::releaseFor('cart', (string) $cart->id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $order->refresh();

        // Notify sellers only when order is visible (COD processing or paid ? processing).
        if (in_array($order->status, ['processing', 'approved'], true)
            && in_array($order->payment_status, ['pending', 'paid', 'partial_paid'], true)
            && ($paymentMethod === 'cod' || in_array($order->payment_status, ['paid', 'partial_paid'], true))) {
            // COD: notify now. Wallet already notified via markPaymentSuccess.
            if ($paymentMethod === 'cod') {
                OrderWorkflowService::notifySellers($order);
            }
        }

        // Do not auto-book courier until seller approves (CourierBookingService gates on approved).

        $response = [
            'success' => true,
            'message' => in_array($paymentMethod, ['cod', 'wallet'], true)
                ? 'Order placed successfully'
                : 'Order placed. Complete payment from the order page when you are ready.',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'online_amount' => (float) ($order->online_amount ?? 0),
                'cod_amount' => (float) ($order->cod_amount ?? 0),
                'partial_payment_percent' => $order->partial_payment_percent,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
            ],
        ];

        // Online gateways are started later via retry-payment (Pay Now on order detail).
        if ($paymentMethod === 'wallet') {
            $response['message'] = 'Order placed successfully. Paid with wallet.';
        } elseif ($paymentMethod === 'paypal') {
            $response['message'] = 'Order placed. PayPal payment can be completed from the order page when available.';
        }

        ActivityLogger::log([
            'action_type' => 'place_order',
            'action_by' => $user->id,
            'target_table' => 'orders',
            'action_on' => $order->id,
            'description' => "Order placed {$order->order_number} ({$paymentMethod}) total {$order->total}",
        ], $request);

        WachatService::notifyOrderPlacedCustomer($order->fresh('user'));

        return response()->json($response, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $user->orders()
            ->with(['items.product.media', 'coupon', 'shipments']);

        $status = strtolower(trim((string) $request->input('status', '')));
        if ($status !== '' && $status !== 'all') {
            // Legacy "paid" fulfillment is shown as processing to buyers.
            if ($status === 'processing') {
                $query->whereIn('status', ['processing', 'paid']);
            } else {
                $query->where('status', $status);
            }
        }

        $sort = strtolower(trim((string) $request->input('sort', 'newest')));
        if ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'status') {
            $query->orderBy('status')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $orders = $query->paginate(20);

        $items = collect($orders->items())->map(function ($order) {
            $this->attachTrackingFromShipments($order);
            $arr = $this->orderToArray($order);
            $arr['complete_order_status'] = $order->effective_status;
            $arr['seller_status_summary'] = $order->shipments->isNotEmpty()
                ? $order->shipments->pluck('status')->unique()->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->join(', ')
                : (in_array($order->status, ['cancelled', 'refunded']) ? ucfirst($order->status) : 'Pending');
            $arr['payment_status'] = $order->payment_status;
            $arr['coupon'] = $order->relationLoaded('coupon') && $order->coupon ? ['code' => $order->coupon->code] : null;
            return $arr;
        })->values()->all();

        return response()->json([
            'success' => true,
            'orders' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Convert order to a plain array for API (ensures consistent keys for mobile app).
     */
    private function orderToArray(Order $order): array
    {
        return [
            'id' => (int) $order->id,
            'order_number' => (string) ($order->order_number ?? ''),
            'status' => (string) ($order->status ?? ''),
            'total' => (float) $order->total,
            'subtotal' => $order->subtotal !== null ? (float) $order->subtotal : null,
            'online_amount' => $order->online_amount !== null ? (float) $order->online_amount : null,
            'cod_amount' => $order->cod_amount !== null ? (float) $order->cod_amount : null,
            'partial_payment_percent' => $order->partial_payment_percent,
            'payment_method' => (string) ($order->payment_method ?? ''),
            'payment_status' => (string) ($order->payment_status ?? ''),
            'shipping_cost' => $order->shipping_cost !== null ? (float) $order->shipping_cost : null,
            'discount_amount' => $order->discount_amount !== null ? (float) $order->discount_amount : null,
            'marketplace_fee' => $order->marketplace_fee !== null ? (float) $order->marketplace_fee : 0,
            'online_transaction_fee' => $order->online_transaction_fee !== null ? (float) $order->online_transaction_fee : 0,
            'seller_commission_total' => $order->seller_commission_total !== null ? (float) $order->seller_commission_total : 0,
            'platform_revenue' => $order->platform_revenue !== null ? (float) $order->platform_revenue : 0,
            'marketplace_fee_type' => $order->marketplace_fee_type,
            'marketplace_fee_rate' => $order->marketplace_fee_rate !== null ? (float) $order->marketplace_fee_rate : null,
            'online_transaction_fee_type' => $order->online_transaction_fee_type,
            'online_transaction_fee_rate' => $order->online_transaction_fee_rate !== null ? (float) $order->online_transaction_fee_rate : null,
            'created_at' => $order->created_at?->toIso8601String(),
            'tracking_number' => $order->tracking_number ? (string) $order->tracking_number : null,
            'tracking_url' => $order->tracking_url ? (string) $order->tracking_url : null,
            'tracking_carrier' => isset($order->tracking_carrier) ? (string) $order->tracking_carrier : null,
            'items' => $order->relationLoaded('items') ? $order->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => (int) $i->product_id,
                'product_name' => (string) ($i->product_name ?? $i->product?->name ?? ''),
                'quantity' => (int) $i->quantity,
                'price' => (float) $i->price,
                'image_url' => $i->resolveImageUrl(),
                'product_available' => $i->isProductAvailable(),
                'variant_label' => null,
                'options' => $i->options,
            ])->values()->all() : [],
            'shipping_address' => $order->relationLoaded('shippingAddress') && $order->shippingAddress
                ? $order->shippingAddress->toArray()
                : null,
        ];
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $order->load(['items.product.media', 'items.store', 'shippingAddress', 'timeline', 'coupon', 'shipments.store']);
        $this->attachTrackingFromShipments($order);

        $order->has_open_dispute = $order->disputes()->whereIn('status', ['open', 'seller_responded'])->exists();
        $order->has_pending_refund = $order->refunds()->where('status', 'pending')->exists();

        // Variant images: load all variants referenced by order items for efficient lookup
        $variantIds = $order->items->map(fn ($i) => $i->options['variant_id'] ?? null)->filter()->unique()->values()->all();
        $variants = !empty($variantIds) ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

        // Add image_url to each item (variant → snapshot → live product incl. soft-deleted)
        foreach ($order->items as $item) {
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            $variant = $variantId > 0 ? $variants->get($variantId) : null;
            $variantImagePath = $variant ? ($variant->image_path ?? (is_array($variant->image_paths ?? null) && !empty($variant->image_paths) ? $variant->image_paths[0] : null)) : null;
            $item->image_url = $item->resolveImageUrl($variantImagePath);
            $item->product_available = $item->isProductAvailable();
            // Soft-deleted listings stay visible on orders but are not shoppable.
            if ($item->product && $item->product->trashed()) {
                $item->product->setAttribute('slug', null);
            }
        }

        // Only return items with price > 0 (hide ghost zero-value lines for variant-only orders)
        $order->setRelation('items', $order->items->filter(fn ($i) => (float) $i->price > 0)->values());

        // Attach product names to each shipment so customer can see which items are in each tracking
        foreach ($order->shipments as $shipment) {
            $productNames = $order->items->filter(function ($item) use ($shipment) {
                if ($shipment->store_id) {
                    return (int) $item->store_id === (int) $shipment->store_id;
                }
                return (int) $item->seller_id === (int) $shipment->seller_id;
            })->pluck('product_name')->unique()->values()->all();
            \App\Services\CourierShipmentPresenter::enrich($shipment);
            $shipment->setAttribute('product_names', $productNames);
            // Never expose courier API errors / raw responses to customers.
            $shipment->makeHidden(['tcs_raw_response', 'lcs_raw_response']);
            $shipment->setAttribute('lcs_booking_error', null);
            $shipment->setAttribute('courier_booking_error', null);
        }

        // Complete order status (fulfillment) for customer display; payment_status remains separate
        $order->complete_order_status = $order->effective_status;

        // Per-seller groups (multi-store cart): status, tracking, refunds per seller
        $order->seller_groups = \App\Services\SellerFulfillmentService::buyerSellerGroups($order);

        // Per-item seller product ordered status (fulfillment + shipment)
        foreach ($order->items as $item) {
            $shipment = $order->shipments->first(function ($s) use ($item) {
                if ($item->store_id && $s->store_id) {
                    return (int) $item->store_id === (int) $s->store_id;
                }
                return (int) $item->seller_id === (int) $s->seller_id && empty($s->store_id);
            });
            $fs = $item->fulfillment_status ?: 'pending';
            if (in_array($fs, ['rejected', 'cancelled'], true)) {
                $item->seller_portion_status = $fs;
            } elseif ($shipment) {
                $item->seller_portion_status = $shipment->status === 'in_transit' ? 'shipped' : $shipment->status;
            } else {
                $item->seller_portion_status = in_array($fs, ['processing', 'approved', 'shipped', 'delivered'], true)
                    ? $fs
                    : (in_array($order->status, ['cancelled', 'refunded'], true) ? $order->status : 'processing');
            }
        }

        // Customer-visible refunds (partial seller rejects)
        $order->loadMissing('refunds');
        $refundedTotal = round((float) $order->refunds->where('status', 'completed')->sum('amount'), 2);
        if ($refundedTotal <= 0) {
            $refundedTotal = round((float) $order->items->sum(fn ($i) => (float) ($i->refund_amount ?? 0)), 2);
        }
        // Payment status must stay as gateway payment state (paid / partial_paid) while any
        // seller portion is still active — do not show partial_refunded for partial rejects.
        $hasActivePortion = $order->items->contains(function ($i) {
            return (float) $i->price > 0
                && ! in_array($i->fulfillment_status ?? '', ['rejected', 'cancelled'], true);
        });
        if ($hasActivePortion && in_array($order->payment_status, ['partial_refunded', 'refunded'], true)) {
            $restored = ((float) ($order->cod_amount ?? 0) > 0.009
                || $order->payment_method === 'jazzcash_partial')
                ? 'partial_paid'
                : 'paid';
            if ($order->payment_status !== $restored) {
                $order->payment_status = $restored;
                $order->saveQuietly();
            }
        }

        $order->setAttribute('refunded_total', $refundedTotal);
        $order->setAttribute('remaining_total', max(0, round((float) $order->total - $refundedTotal, 2)));
        $order->setAttribute('refunds_summary', $order->refunds->map(fn ($r) => [
            'id' => $r->id,
            'amount' => (float) $r->amount,
            'reason' => $r->reason,
            'status' => $r->status,
            'created_at' => $r->created_at?->toISOString(),
        ])->values());

        // Customer should not see seller commission / platform revenue internals
        $order->makeHidden([
            'seller_commission_total',
            'platform_revenue',
            'seller_marketplace_fee_total',
            'seller_online_transaction_fee_total',
            'seller_marketplace_fee_type',
            'seller_marketplace_fee_rate',
            'seller_online_transaction_fee_type',
            'seller_online_transaction_fee_rate',
            'seller_commission_type',
            'seller_commission_rate',
        ]);
        \App\Services\OrderFeeBreakdown::attachCustomerView($order);
        foreach ($order->items as $item) {
            $item->makeHidden(['commission_amount', 'marketplace_fee_allocated', 'online_transaction_fee_allocated']);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * For single-shipment (single-seller) orders, set order tracking from that shipment for customer display.
     * For multi-seller orders, do not set one tracking on the order; customer sees tracking per shipment.
     */
    private function attachTrackingFromShipments(Order $order): void
    {
        if (!$order->relationLoaded('shipments')) {
            return;
        }
        $shipments = $order->shipments;
        if ($shipments->count() === 1) {
            $first = $shipments->first();
            $cn = \App\Services\CourierShipmentPresenter::cnNumber($first);
            if (!$order->tracking_number && $cn !== '') {
                $order->tracking_number = $cn;
            } elseif (!$order->tracking_number) {
                $order->tracking_number = $first->tracking_number;
            }
            \App\Services\CourierShipmentPresenter::enrich($first);
            if (!$order->tracking_url) {
                $order->tracking_url = \App\Services\CourierShipmentPresenter::trackingUrl($first);
            }
            if ($first->carrier) {
                $order->tracking_carrier = $first->carrier;
            }
        }
        // Multi-seller: order.tracking_* left as-is (null or previous); frontend uses order.shipments for per-seller tracking
    }

    /**
     * Retry payment for pending unpaid JazzCash / Stripe / Easypaisa orders.
     */
    public function retryPayment(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $method = (string) ($order->payment_method ?? '');
        if (!in_array($method, ['jazzcash', 'jazzcash_partial', 'stripe', 'easypaisa'], true)) {
            return response()->json(['success' => false, 'message' => 'Payment retry is not available for this order.'], 422);
        }
        if ($order->status !== 'pending' || !in_array($order->payment_status, ['pending'], true)) {
            return response()->json(['success' => false, 'message' => 'Only unpaid pending orders can retry payment.'], 422);
        }

        $request->validate([
            'payment_phone' => 'nullable|string|max:20',
            'payment_cnic' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $order->loadMissing(['payments', 'shippingAddress']);
        $gateway = $method === 'jazzcash_partial' ? 'jazzcash' : $method;
        $payment = $order->payments()->where('gateway', $gateway)->where('status', 'pending')->first()
            ?? $order->payments()->where('gateway', $gateway)->latest('id')->first();

        $storedMeta = is_array($payment?->gateway_response) ? $payment->gateway_response : [];
        $phoneInput = $request->input('payment_phone')
            ?: ($storedMeta['payment_phone'] ?? null)
            ?: $user->phone
            ?: $order->shippingAddress?->phone;
        $cnicInput = $request->input('payment_cnic') ?: ($storedMeta['payment_cnic'] ?? null);

        if (!$payment) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => $gateway,
                'amount' => (float) ($order->online_amount ?: $order->total),
                'currency' => $order->market === 'AE' ? 'AED' : 'PKR',
                'status' => 'pending',
                'gateway_response' => array_filter([
                    'payment_phone' => PhoneHelper::normalize($phoneInput),
                    'payment_cnic' => $cnicInput,
                ]),
            ]);
        } elseif ($payment->status !== 'pending') {
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => $gateway,
                'amount' => (float) ($order->online_amount ?: $order->total),
                'currency' => $order->market === 'AE' ? 'AED' : 'PKR',
                'status' => 'pending',
                'gateway_response' => array_filter([
                    'payment_phone' => PhoneHelper::normalize($phoneInput),
                    'payment_cnic' => $cnicInput,
                ]),
            ]);
        } else {
            // Refresh stored JazzCash details for this retry.
            $meta = is_array($payment->gateway_response) ? $payment->gateway_response : [];
            if ($phoneInput) {
                $meta['payment_phone'] = PhoneHelper::normalize($phoneInput) ?: $phoneInput;
            }
            if ($cnicInput) {
                $meta['payment_cnic'] = $cnicInput;
            }
            if ($meta !== (array) $payment->gateway_response) {
                $payment->update(['gateway_response' => $meta]);
            }
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/');
        $orderDetailUrl = $frontendUrl . '/customer/orders/' . $order->id;

        $response = [
            'success' => true,
            'message' => 'Redirect to payment',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => (float) $order->total,
            ],
        ];

        if ($method === 'stripe') {
            $successUrl = $orderDetailUrl . '?paid=1';
            $cancelUrl = $orderDetailUrl . '?paid=0';
            $checkoutUrl = (new StripeService())->createCheckoutSession($order, $payment, $successUrl, $cancelUrl);
            if ($checkoutUrl) {
                $response['checkout_url'] = $checkoutUrl;
            } else {
                return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 422);
            }
        } elseif ($method === 'easypaisa') {
            $phone = PhoneHelper::normalize($phoneInput);
            $checkoutData = (new EasypaisaService())->getCheckoutData($order, $payment, $phone, $user->email);
            if ($checkoutData) {
                $response['checkout_url'] = $checkoutData['url'];
                $response['checkout_method'] = $checkoutData['method'];
                $response['checkout_params'] = $checkoutData['params'];
            } else {
                return response()->json(['success' => false, 'message' => 'Easypaisa is not configured.'], 422);
            }
        } else {
            $phone = JazzCashService::normalizeMobile($phoneInput);
            if ($phone === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'JazzCash mobile number is required (03XXXXXXXXX (also accepts 923?)).',
                ], 422);
            }
            if (JazzCashService::normalizeCnic($cnicInput) === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNIC is required for JazzCash (last 6 digits or full CNIC).',
                ], 422);
            }
            $pay = (new JazzCashService())->processOrderPayment($order, $payment, $phone, $cnicInput);
            $order->refresh();
            $response['order']['status'] = $order->status;
            $response['order']['payment_status'] = $order->payment_status;
            $response['jazzcash_mode'] = $pay['mode'] ?? JazzCashService::checkoutMode();
            $response['payment_ok'] = (bool) ($pay['payment_ok'] ?? false);
            $response['payment_status'] = $pay['payment_status'] ?? $order->payment_status;
            $response['response_code'] = $pay['response_code'] ?? null;
            $response['message'] = $pay['message'] ?? $response['message'];
            if (!empty($pay['checkout_url'])) {
                $response['checkout_url'] = $pay['checkout_url'];
                $response['checkout_method'] = $pay['checkout_method'] ?? 'POST';
                $response['checkout_params'] = $pay['checkout_params'] ?? [];
            }
        }

        return response()->json($response);
    }

    /**
     * Customer cancels their order, or requests cancellation when already paid/processing.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);
        $reason = $request->input('cancellation_reason') ?: $request->input('reason') ?: 'Cancelled by customer';

        // Unpaid pending: hard cancel + restore stock
        if ($order->status === 'pending' && $order->payment_status !== 'paid' && $order->payment_status !== 'partial_paid') {
            OrderWorkflowService::restoreStock($order);
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'note' => 'Order cancelled by customer (unpaid)',
            ]);

            ActivityLogger::log([
                'action_type' => 'cancel_order',
                'action_by' => $request->user()->id,
                'target_table' => 'orders',
                'action_on' => $order->id,
                'description' => "Order cancelled (unpaid): {$order->order_number}",
            ], $request);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled',
                'order' => ['id' => $order->id, 'status' => $order->status],
            ]);
        }

        // Paid / processing / approved: request cancellation (seller must approve)
        if (in_array($order->status, ['processing', 'approved', 'paid'], true)
            || in_array($order->payment_status, ['paid', 'partial_paid'], true)) {
            if (in_array($order->status, ['shipped', 'delivered', 'completed', 'cancelled', 'refunded', 'cancellation_requested'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order can no longer be cancelled. It may already be shipped or completed.',
                ], 422);
            }

            $order->update([
                'status' => 'cancellation_requested',
                'cancellation_reason' => $reason,
                'cancellation_requested_at' => now(),
            ]);
            OrderTimeline::create([
                'order_id' => $order->id,
                'status' => 'cancellation_requested',
                'note' => 'Customer requested cancellation: ' . $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancellation requested. Waiting for seller approval.',
                'order' => ['id' => $order->id, 'status' => $order->status],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'This order can no longer be cancelled. It may already be shipped or completed.',
        ], 422);
    }

    /**
     * Alias for cancel on processing/paid orders (cancellation request).
     */
    public function requestCancellation(Request $request, Order $order): JsonResponse
    {
        return $this->cancel($request, $order);
    }

    /**
     * Customer updates their order (e.g. notes). Only allowed when order is pending or paid.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $allowed = ['pending', 'paid'];
        if (!in_array($order->status, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This order can no longer be updated.',
            ], 422);
        }

        $request->validate([
            'customer_notes' => 'nullable|string|max:500',
        ]);

        $order->update(['customer_notes' => $request->input('customer_notes')]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated',
            'order' => $order->fresh(['items', 'shippingAddress', 'timeline']),
        ]);
    }

    private static function isPaymentMethodEnabled(string $method): bool
    {
        return match ($method) {
            'cod' => (string) Setting::get('payment_cod_enabled', '1') !== '0',
            'stripe' => (string) Setting::get('stripe_enabled', '0') === '1',
            'paypal' => (string) Setting::get('paypal_enabled', '1') === '1',
            'jazzcash', 'jazzcash_partial' => (string) Setting::get('jazzcash_enabled', '0') === '1',
            'easypaisa' => (string) Setting::get('easypaisa_enabled', '0') === '1',
            'wallet' => true,
            default => false,
        };
    }
}
