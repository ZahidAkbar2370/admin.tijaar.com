@extends('admin.layouts.app')
@section('title', 'Orders — Seller #' . $user->id)
@section('admin-content')
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Orders</h1>
    <p class="text-sm text-gray-500 mb-6">Orders containing products from this seller's store.</p>
    @if (!$storeId)<p class="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-xl p-4">No store linked — orders will appear once a store exists.</p>
    @elseif ($sellerOrders->isEmpty())<p class="text-sm text-gray-500">No orders found.</p>
    @else
    <div class="overflow-x-auto rounded-xl border border-gray-100"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Order</th><th class="px-4 py-3 text-left">Buyer</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Payment</th><th class="px-4 py-3 text-left">Total</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y">@foreach ($sellerOrders as $order)<tr><td class="px-4 py-3 font-medium">{{ $order->order_number ?? '#'.$order->id }}</td><td class="px-4 py-3">{{ $order->user?->name ?? '—' }}</td><td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td><td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? '—')) }}</td><td class="px-4 py-3">{{ number_format((float)$order->total, 0) }} PKR</td><td class="px-4 py-3 text-gray-500">{{ $order->created_at?->format('M d, Y') }}</td><td class="px-4 py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="text-amber-700 text-xs font-medium hover:underline">View</a></td></tr>@endforeach</tbody></table></div>
    @if ($sellerOrders->hasPages())<div class="mt-4">{{ $sellerOrders->links() }}</div>@endif
    @endif
</section>
@endsection
