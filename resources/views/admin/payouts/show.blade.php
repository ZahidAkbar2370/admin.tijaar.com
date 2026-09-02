@extends('admin.layouts.app')

@section('title', 'Payout ' . $payout->payout_number)

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.payouts.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Payouts
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Payout {{ $payout->payout_number }}</h1>
                <p class="text-gray-500 text-sm">{{ $payout->created_at->format('M d, Y H:i') }}</p>
            </div>
            <span class="inline-flex px-4 py-2 rounded-xl text-sm font-medium
                @if($payout->status === 'paid') bg-emerald-100 text-emerald-700
                @elseif($payout->status === 'rejected') bg-red-100 text-red-700
                @elseif($payout->status === 'approved') bg-blue-100 text-blue-700
                @else bg-amber-100 text-amber-700 @endif">{{ $payout->status }}</span>
        </div>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">Seller</h3>
            <p class="text-gray-600">{{ $payout->user?->name }}</p>
            <p class="text-gray-500 text-sm">{{ $payout->user?->email }}</p>
        </div>
        <div>
            <h3 class="font-semibold text-gray-900 mb-2">Amount</h3>
            <p class="text-2xl font-bold text-primary">{{ number_format($payout->amount, 2) }} PKR</p>
            <p class="text-sm text-gray-500">Method: {{ $payout->method }}</p>
            @if ($payout->bank_name)
            <p class="text-sm text-gray-600 mt-2">{{ $payout->bank_name }} — {{ $payout->bank_account_holder }}</p>
            @endif
        </div>
    </div>
    <div class="border-t border-gray-100">
        <h3 class="px-6 py-4 font-semibold text-gray-900">Items</h3>
        <div class="divide-y divide-gray-100">
            @foreach ($payout->items as $i)
            <div class="px-6 py-4 flex justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $i->orderItem?->product_name ?? 'Order Item #' . $i->order_item_id }}</p>
                    <p class="text-sm text-gray-500">Order #{{ $i->orderItem?->order?->order_number ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm">Subtotal: {{ number_format($i->order_item_subtotal, 2) }}</p>
                    <p class="text-sm text-red-600">- Commission: {{ number_format($i->commission_amount, 2) }}</p>
                    <p class="font-semibold text-primary">Net: {{ number_format($i->net_amount, 2) }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @if ($payout->status === 'pending')
    <div class="p-6 border-t border-gray-100 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}" class="inline">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl text-sm">Approve</button>
        </form>
        <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" class="inline" x-data="{ show: false }">
            @csrf
            <div x-show="!show">
                <button type="button" @click="show = true" class="px-5 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-xl text-sm">Reject</button>
            </div>
            <div x-show="show" x-cloak class="inline-flex gap-2">
                <input type="text" name="reason" placeholder="Rejection reason" required class="px-4 py-2 rounded-xl border border-gray-200 text-sm w-64">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm">Confirm Reject</button>
                <button type="button" @click="show = false" class="px-4 py-2 text-gray-600">Cancel</button>
            </div>
        </form>
    </div>
    @elseif ($payout->status === 'approved')
    <div class="p-6 border-t border-gray-100">
        <form method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}" class="inline">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Mark as Paid</button>
        </form>
    </div>
    @endif
    @if ($payout->rejection_reason)
    <div class="p-6 border-t border-gray-100 bg-red-50">
        <p class="text-sm text-red-700"><strong>Rejection reason:</strong> {{ $payout->rejection_reason }}</p>
    </div>
    @endif
</div>
@endsection
