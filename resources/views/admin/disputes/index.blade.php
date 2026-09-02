@extends('admin.layouts.app')

@section('title', 'Disputes')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Disputes</h1>
    <p class="text-sm text-gray-500 mt-1">Customer disputes and returns</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.disputes.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Dispute #, Order #, user..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="seller_responded" {{ request('status') === 'seller_responded' ? 'selected' : '' }}>Seller Responded</option>
                    <option value="admin_review" {{ request('status') === 'admin_review' ? 'selected' : '' }}>Admin Review</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($disputes as $d)
        <a href="{{ route('admin.disputes.show', $d) }}" class="block p-6 hover:bg-gray-50/50 transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-gray-900">{{ $d->dispute_number }}</p>
                    <p class="text-sm text-gray-500">Order #{{ $d->order?->order_number }} · {{ $d->user?->name }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $d->type }} – {{ Str::limit($d->description, 80) }}</p>
                </div>
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium
                    @if($d->status === 'resolved') bg-emerald-100 text-emerald-700
                    @elseif($d->status === 'rejected') bg-red-100 text-red-700
                    @else bg-amber-100 text-amber-700 @endif">{{ $d->status }}</span>
            </div>
        </a>
        @empty
        <div class="p-16 text-center text-gray-500">No disputes</div>
        @endforelse
    </div>
    @if ($disputes->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $disputes->links() }}</div>
    @endif
</div>
@endsection
