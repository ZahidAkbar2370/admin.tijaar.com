@extends('admin.layouts.app')

@section('title', 'Payouts')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Payouts</h1>
        <p class="text-sm text-gray-500 mt-1">Manage seller payout requests</p>
    </div>
</div>

{{-- Config --}}
<div class="mb-6 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm" x-data="{ open: false }">
    <button type="button" @click="open = !open" class="flex items-center gap-2 text-sm font-semibold text-gray-700">
        <svg class="w-4 h-4 transition" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        Payout Config (Min thresholds)
    </button>
    <form method="POST" action="{{ route('admin.payouts.update-config') }}" x-show="open" x-cloak class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Business min (PKR)</label>
            <input type="number" name="payout_min_threshold" value="{{ $config['payout_min_threshold'] ?? 1000 }}" step="1" min="0" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Private min (PKR)</label>
            <input type="number" name="private_payout_threshold" value="{{ $config['private_payout_threshold'] ?? 500 }}" step="1" min="0" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm">
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium">Save</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.payouts.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Payout #, seller..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Payout</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Seller</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payouts as $p)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 font-mono font-semibold text-gray-900">{{ $p->payout_number }}</td>
                    <td class="px-6 py-4 text-sm">
                        <p class="font-medium text-gray-900">{{ $p->user?->name ?? '—' }}</p>
                        <p class="text-gray-500 text-xs">{{ $p->user?->email ?? '' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($p->seller_type) }}</td>
                    <td class="px-6 py-4 font-semibold">{{ number_format($p->amount, 2) }} PKR</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium
                            @if($p->status === 'paid') bg-emerald-100 text-emerald-700
                            @elseif($p->status === 'rejected') bg-red-100 text-red-700
                            @elseif($p->status === 'approved') bg-blue-100 text-blue-700
                            @else bg-amber-100 text-amber-700 @endif">{{ $p->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $p->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.payouts.show', $p) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <p class="text-gray-500 font-medium">No payouts found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($payouts->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $payouts->links() }}</div>
    @endif
</div>
@endsection
