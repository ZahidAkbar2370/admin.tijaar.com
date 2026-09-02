@extends('admin.layouts.app')

@section('title', 'Transactions')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Transactions</h1>
    <p class="text-sm text-gray-500 mt-1">Order payment transactions with gateway and status details</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, reference, gateway..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    @foreach (['pending', 'completed', 'failed', 'refunded'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Gateway</label>
                <select name="gateway" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    @foreach (['cod', 'wallet', 'jazzcash', 'stripe', 'paypal', 'easypaisa'] as $gw)
                        <option value="{{ $gw }}" @selected(request('gateway') === $gw)>{{ ucfirst($gw) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Order</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Gateway</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Paid at</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($transactions as $txn)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-6 py-4 text-sm font-mono text-gray-500">#{{ $txn->id }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if ($txn->order)
                            <a href="{{ route('admin.orders.show', $txn->order) }}" class="font-semibold text-primary hover:underline">{{ $txn->order->order_number }}</a>
                            <p class="text-xs text-gray-500">{{ $txn->order->user?->name }}</p>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm capitalize">{{ $txn->gateway ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-semibold">{{ number_format((float) $txn->amount, 2) }} {{ $txn->currency ?? 'PKR' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium
                            {{ $txn->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($txn->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ ucfirst($txn->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $txn->paid_at?->format('M d, Y g:i A') ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.transactions.show', $txn) }}" class="text-sm text-primary hover:underline">Details</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
