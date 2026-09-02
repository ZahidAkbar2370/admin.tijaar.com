@extends('admin.layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Orders
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Order {{ $order->order_number }}</h1>
                <p class="text-gray-500 text-sm">{{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                @php $orderStatus = $order->effective_status ?? $order->status; @endphp
                <span class="inline-flex px-4 py-2 rounded-xl text-sm font-medium
                    @if(in_array($orderStatus, ['completed','delivered'])) bg-emerald-100 text-emerald-700
                    @elseif($orderStatus === 'cancelled' || $orderStatus === 'refunded') bg-red-100 text-red-700
                    @else bg-amber-100 text-amber-700 @endif" title="Order status">{{ $orderStatus }}</span>
                <span class="inline-flex px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-700" title="Payment">{{ $order->payment_status ?? '—' }}</span>
                @php
                    $onlineMethods = ['jazzcash', 'jazzcash_partial', 'easypaisa', 'stripe', 'paypal'];
                    $canMarkPaid = in_array((string) $order->payment_method, $onlineMethods, true)
                        && in_array((string) $order->payment_status, ['pending', 'failed'], true)
                        && !in_array($order->status, ['cancelled', 'refunded'], true);
                @endphp
                @if ($canMarkPaid)
                    <form method="POST" action="{{ route('admin.orders.mark-payment-paid', $order) }}" onsubmit="return confirm('Mark this online payment as paid/approved?');">
                        @csrf
                        <button type="submit" class="inline-flex px-4 py-2 rounded-xl text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">
                            Mark payment paid
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">Customer</h3>
            <p class="text-gray-600">{{ $order->user?->name }}</p>
            <p class="text-gray-500 text-sm">{{ $order->user?->email }}</p>
            <p class="text-gray-500 text-sm">{{ $order->user?->phone ?? '—' }}</p>
        </div>
        @if ($order->shippingAddress)
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">Shipping Address</h3>
            <p class="text-gray-600 text-sm">
                {{ $order->shippingAddress->address_line_1 }}<br>
                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state ?? '' }} {{ $order->shippingAddress->zip_code ?? '' }}<br>
                {{ $order->shippingAddress->country }}<br>
                {{ $order->shippingAddress->phone ?? '' }}
            </p>
        </div>
        @endif
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">Payment Method</h3>
            <p class="text-gray-600 capitalize">{{ $order->payment_method ? str_replace('_', ' ', $order->payment_method) : '—' }}</p>
            <p class="text-sm text-gray-500 mt-1">Status: <span class="font-medium text-gray-700">{{ $order->payment_status ?? '—' }}</span></p>
            @if ($order->payments->isNotEmpty())
                <ul class="mt-2 space-y-1 text-xs text-gray-500">
                    @foreach ($order->payments as $payment)
                        <li>
                            {{ ucfirst($payment->gateway ?? 'payment') }}:
                            {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency ?? 'PKR' }}
                            — {{ $payment->status }}
                            @if ($payment->gateway_reference)
                                <span class="font-mono">({{ $payment->gateway_reference }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (in_array((string) $order->payment_method, ['jazzcash', 'jazzcash_partial', 'easypaisa', 'stripe', 'paypal'], true)
                && (string) $order->payment_status === 'pending')
                <p class="text-xs text-amber-700 mt-2">Online payment is still pending. CN booking waits until payment is paid (or mark paid manually above).</p>
            @endif
        </div>
    </div>

    @if ($order->shipments->isNotEmpty() || $order->tracking_number)
    <div class="px-6 pb-6 border-t border-gray-100">
        <h3 class="font-semibold text-gray-900 mb-4">Shipping &amp; CN numbers</h3>
        @if ($order->shipments->isNotEmpty())
            <div class="space-y-4">
                @foreach ($order->shipments as $shipment)
                    @php
                        $cn = \App\Services\CourierShipmentPresenter::cnNumber($shipment);
                        $cnLabel = \App\Services\CourierShipmentPresenter::cnLabel($shipment);
                        $carrierLabel = \App\Services\CourierShipmentPresenter::carrierLabel($shipment);
                        $trackingUrl = \App\Services\CourierShipmentPresenter::trackingUrl($shipment);
                    @endphp
                    <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/60">
                        @if ($shipment->store?->name)
                            <p class="text-sm font-medium text-gray-900 mb-1">Store: {{ $shipment->store->name }}</p>
                        @endif
                        @if (!empty($shipment->product_names))
                            <p class="text-sm text-gray-600 mb-2">
                                <span class="font-medium">Products:</span> {{ implode(', ', $shipment->product_names) }}
                            </p>
                        @endif
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-600">Carrier: <span class="font-medium text-gray-900">{{ $carrierLabel }}</span></p>
                                @if ($cn !== '')
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ $cnLabel }}: <span class="font-mono font-semibold text-gray-900">{{ $cn }}</span>
                                    </p>
                                @elseif ($shipment->tracking_number)
                                    <p class="text-sm text-gray-600 mt-1">
                                        Tracking: <span class="font-mono font-medium">{{ $shipment->tracking_number }}</span>
                                    </p>
                                @else
                                    <p class="text-sm text-amber-700 mt-1">No tracking yet</p>
                                @endif
                            </div>
                            <span class="inline-flex px-3 py-1 rounded-lg text-xs font-medium capitalize
                                @if($shipment->status === 'delivered') bg-emerald-100 text-emerald-700
                                @elseif($shipment->status === 'in_transit') bg-blue-100 text-blue-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ str_replace('_', ' ', $shipment->status) }}
                            </span>
                        </div>
                        @if ($trackingUrl)
                            <a href="{{ $trackingUrl }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 mt-3 text-sm text-primary hover:underline">
                                Track on {{ $carrierLabel }} →
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif ($order->tracking_number)
            <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/60">
                <p class="text-sm text-gray-600">
                    Tracking: <span class="font-mono font-semibold text-gray-900">{{ $order->tracking_number }}</span>
                </p>
                @if ($order->tracking_url)
                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 mt-2 text-sm text-primary hover:underline">
                        Track shipment →
                    </a>
                @endif
            </div>
        @endif
    </div>
    @endif

    <div class="border-t border-gray-100">
        <h3 class="px-6 py-4 font-semibold text-gray-900">Items</h3>
        <div class="divide-y divide-gray-100">
            @foreach ($orderItems as $i)
            <div class="px-6 py-4 flex gap-4">
                <div class="w-16 h-16 rounded-lg flex-shrink-0 overflow-hidden bg-gray-100">
                    @if (!empty($i->item_image_url))
                        <img src="{{ $i->item_image_url }}" alt="{{ $i->product_name }}" class="w-full h-full object-cover" />
                    @else
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 text-xs">—</div>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $i->product_name }}</p>
                    <p class="text-sm text-gray-500">Qty: {{ $i->quantity }} × {{ number_format($i->price, 2) }}</p>
                    @if ($i->options && is_array($i->options) && !empty($i->options['variant_attributes']))
                        <p class="text-xs text-gray-600 mt-1">Variant: {{ is_array($i->options['variant_attributes']) ? implode(', ', array_map(fn($k, $v) => $k . ': ' . $v, array_keys($i->options['variant_attributes']), $i->options['variant_attributes'])) : $i->options['variant_attributes'] }}</p>
                    @endif
                    @if ($i->store)<p class="text-xs text-gray-400">Store: {{ $i->store->name }}</p>@endif
                    @if ($i->discount_allocated != null && (float) $i->discount_allocated > 0)
                        <p class="text-xs text-emerald-600 mt-0.5">Discount: −{{ number_format((float) $i->discount_allocated, 2) }} {{ $order->market === 'AE' ? 'AED' : 'PKR' }}</p>
                    @endif
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                    @if ($i->discount_allocated != null && (float) $i->discount_allocated > 0)
                        <p class="text-xs text-gray-500 line-through">{{ number_format($i->quantity * $i->price, 2) }}</p>
                        <p class="font-semibold text-primary">{{ number_format(max(0, $i->quantity * $i->price - (float) $i->discount_allocated), 2) }}</p>
                    @else
                        <p class="font-semibold text-primary">{{ number_format($i->quantity * $i->price, 2) }}</p>
                    @endif
                    @php $sellerStatus = $i->seller_portion_status ?? 'pending'; @endphp
                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-medium capitalize
                        @if(in_array($sellerStatus, ['completed','delivered'])) bg-emerald-100 text-emerald-700
                        @elseif($sellerStatus === 'cancelled' || $sellerStatus === 'refunded') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700 @endif" title="Seller product status">
                        Seller: {{ str_replace('_', ' ', $sellerStatus) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="p-6 border-t border-gray-100 bg-gray-50/50">
        @php
            $itemsSubtotal = $orderItems->sum(fn ($i) => (float) $i->price * (int) $i->quantity);
            $currency = $order->market === 'AE' ? 'AED' : 'PKR';
        @endphp
        @if ($order->subtotal != null || $order->shipping_cost != null || $order->discount_amount != null || $order->coupon_id || (float)($customerFees['total'] ?? 0) > 0 || (float)($sellerFees['total'] ?? 0) > 0)
            @if ($order->subtotal != null)
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Order Price / Subtotal</span>
                    <span>{{ number_format($order->subtotal, 2) }} {{ $currency }}</span>
                </div>
            @endif
            @if ($order->shipping_cost != null && (float) $order->shipping_cost > 0)
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Shipping</span>
                    <span>{{ number_format($order->shipping_cost, 2) }} {{ $currency }}</span>
                </div>
            @endif
            @if ($order->discount_amount != null && (float) $order->discount_amount > 0)
                <div class="flex justify-between text-sm text-emerald-600 mb-2">
                    <span>Coupon discount @if($order->coupon)<span class="text-gray-600 font-medium">({{ $order->coupon->code }})</span>@endif</span>
                    <span>−{{ number_format($order->discount_amount, 2) }} {{ $currency }}</span>
                </div>
            @endif

            <div class="mt-4 mb-3 pt-3 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">Customer fees (charged to buyer)</h4>
                @if ((float) ($customerFees['marketplace_fee'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Marketplace fee @if(($customerFees['marketplace_fee_type'] ?? '') === 'percentage' && ($customerFees['marketplace_fee_rate'] ?? null) !== null)({{ rtrim(rtrim(number_format((float)$customerFees['marketplace_fee_rate'], 2), '0'), '.') }}%)@endif</span>
                        <span>{{ number_format($customerFees['marketplace_fee'], 2) }} {{ $currency }}</span>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mb-2">No customer marketplace fee on this order.</p>
                @endif
                @if ((float) ($customerFees['online_transaction_fee'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Online transaction fee @if(($customerFees['online_transaction_fee_type'] ?? '') === 'percentage' && ($customerFees['online_transaction_fee_rate'] ?? null) !== null)({{ rtrim(rtrim(number_format((float)$customerFees['online_transaction_fee_rate'], 2), '0'), '.') }}%)@endif</span>
                        <span>{{ number_format($customerFees['online_transaction_fee'], 2) }} {{ $currency }}</span>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mb-2">No customer online transaction fee on this order.</p>
                @endif
            </div>

            <div class="mt-4 mb-3 pt-3 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">Seller fees (deducted from seller earnings)</h4>
                @if ((float) ($sellerFees['marketplace_fee'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-amber-700 mb-2">
                        <span>Marketplace fee @if(($sellerFees['marketplace_fee_type'] ?? '') === 'percentage' && ($sellerFees['marketplace_fee_rate'] ?? null) !== null)({{ rtrim(rtrim(number_format((float)$sellerFees['marketplace_fee_rate'], 2), '0'), '.') }}%)@endif</span>
                        <span>{{ number_format($sellerFees['marketplace_fee'], 2) }} {{ $currency }}</span>
                    </div>
                @endif
                @if ((float) ($sellerFees['online_transaction_fee'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-amber-700 mb-2">
                        <span>Online transaction fee @if(($sellerFees['online_transaction_fee_type'] ?? '') === 'percentage' && ($sellerFees['online_transaction_fee_rate'] ?? null) !== null)({{ rtrim(rtrim(number_format((float)$sellerFees['online_transaction_fee_rate'], 2), '0'), '.') }}%)@endif</span>
                        <span>{{ number_format($sellerFees['online_transaction_fee'], 2) }} {{ $currency }}</span>
                    </div>
                @endif
                @if ((float) ($sellerFees['order_commission'] ?? 0) > 0)
                    <div class="flex justify-between text-sm text-amber-700 mb-2">
                        <span>Order commission @if(($sellerFees['order_commission_type'] ?? '') === 'percentage' && ($sellerFees['order_commission_rate'] ?? null) !== null)({{ rtrim(rtrim(number_format((float)$sellerFees['order_commission_rate'], 2), '0'), '.') }}%)@endif</span>
                        <span>{{ number_format($sellerFees['order_commission'], 2) }} {{ $currency }}</span>
                    </div>
                @endif
                @if ((float) ($sellerFees['total'] ?? 0) <= 0)
                    <p class="text-xs text-gray-500 mb-2">No seller-side fees recorded on this order.</p>
                @endif
            </div>

            @if ((float) ($order->platform_revenue ?? 0) > 0)
                <div class="flex justify-between text-sm font-medium text-emerald-700 mb-2 pt-2 border-t border-gray-200">
                    <span>Tijaar revenue (this order)</span>
                    <span>{{ number_format($order->platform_revenue, 2) }} {{ $currency }}</span>
                </div>
            @endif
        @endif
        <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 mt-2">
            <span>Customer Total</span>
            <span class="text-primary">{{ number_format($order->total, 2) }} {{ $currency }}</span>
        </div>
    </div>
</div>
@endsection
