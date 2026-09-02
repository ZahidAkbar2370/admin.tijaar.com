@extends('admin.layouts.app')
@section('title', 'Orders — Customer #' . $user->id)
@section('admin-content')
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h1 class="text-lg font-bold text-gray-900">Orders</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.orders', [$user, 'order_role' => 'buyer']) }}" class="px-4 py-2 rounded-xl text-sm font-medium {{ ($orderRole ?? 'buyer') === 'buyer' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600' }}">Order as Buyer</a>
            <a href="{{ route('admin.users.orders', [$user, 'order_role' => 'seller']) }}" class="px-4 py-2 rounded-xl text-sm font-medium {{ ($orderRole ?? 'buyer') === 'seller' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600' }}">Order as Seller</a>
        </div>
    </div>
    @if ($customerOrders->isEmpty())
        <p class="text-sm text-gray-500">No orders found.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Order</th>@if (($orderRole ?? 'buyer') === 'seller')<th class="px-4 py-3 text-left">Buyer</th>@endif<th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Payment</th><th class="px-4 py-3 text-left">Total</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($customerOrders as $order)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $order->order_number ?? '#'.$order->id }}</td>
                        @if (($orderRole ?? 'buyer') === 'seller')<td class="px-4 py-3">{{ $order->user?->name ?? '—' }}</td>@endif
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? '—')) }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $order->total, 0) }} PKR</td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->created_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="text-primary text-xs font-medium hover:underline">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($customerOrders->hasPages())<div class="mt-4">{{ $customerOrders->links() }}</div>@endif
    @endif
</section>
@endsection
