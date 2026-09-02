@extends('admin.layouts.app')

@section('title', 'Refunds')

@section('admin-content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Refunds</h1>
        <p class="text-sm text-gray-500 mt-1">Process refund requests</p>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.refunds.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Order</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($refunds as $r)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 font-mono text-sm">{{ $r->id }}</td>
                    <td class="px-6 py-4 text-sm">{{ $r->order?->order_number ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-semibold">{{ number_format($r->amount, 2) }}</td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $r->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $r->status }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $r->created_at?->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.refunds.show', $r) }}" class="text-primary hover:underline text-sm font-medium">View / Process</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No refunds</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($refunds->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $refunds->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
