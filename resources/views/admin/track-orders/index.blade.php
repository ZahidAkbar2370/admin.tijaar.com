@extends('admin.layouts.app')

@section('title', 'Track Orders')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Track Orders</h1>
    <p class="text-sm text-gray-500 mt-1">Active orders with courier details and latest provider responses</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, CN, customer..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Courier</label>
                <select name="carrier" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="leopards" @selected(request('carrier') === 'leopards')>Leopards</option>
                    <option value="tcs" @selected(request('carrier') === 'tcs')>TCS</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>

    <div class="divide-y divide-gray-100">
        @forelse ($orders as $order)
        <div class="p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-primary hover:underline">{{ $order->order_number }}</a>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $order->user?->name }} · {{ ucfirst($order->status) }}</p>
                </div>
                <span class="text-xs text-gray-400">Updated {{ $order->updated_at?->diffForHumans() }}</span>
            </div>

            <div class="space-y-4">
                @foreach ($order->shipments as $shipment)
                @php
                    $cn = \App\Services\CourierShipmentPresenter::cnNumber($shipment);
                    $carrierLabel = \App\Services\CourierShipmentPresenter::carrierLabel($shipment);
                    $trackUrl = \App\Services\CourierShipmentPresenter::trackingUrl($shipment);
                    $raw = $shipment->tcs_raw_response ?? $shipment->lcs_raw_response ?? null;
                @endphp
                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $carrierLabel }}</p>
                            <p class="text-xs text-gray-500">{{ $shipment->store?->name ?? 'Seller shipment' }} · {{ ucfirst($shipment->status ?? 'pending') }}</p>
                        </div>
                        @if ($trackUrl)
                            <a href="{{ $trackUrl }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:underline">Track on courier site →</a>
                        @endif
                    </div>
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm mb-3">
                        <div><dt class="text-xs text-gray-500">CN / Tracking</dt><dd class="font-mono font-medium">{{ $cn ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Shipped</dt><dd>{{ $shipment->shipped_at?->format('M d, Y') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Booking ID</dt><dd class="font-mono text-xs">{{ $shipment->tcs_booking_id ?? $shipment->lcs_booking_id ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Cost</dt><dd>{{ number_format((float) $shipment->shipping_cost, 2) }} PKR</dd></div>
                    </dl>
                    @if ($raw)
                        <details class="text-xs">
                            <summary class="cursor-pointer text-gray-600 font-medium">Latest provider response</summary>
                            <pre class="mt-2 bg-white border border-gray-100 rounded-lg p-3 overflow-x-auto max-h-48">{{ json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="px-6 py-16 text-center text-gray-500">No active shipments to track.</div>
        @endforelse
    </div>

    @if ($orders->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
