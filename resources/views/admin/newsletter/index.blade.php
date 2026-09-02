@extends('admin.layouts.app')

@section('title', 'Newsletter Subscribers')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Newsletter Subscribers</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $subscribers->total() }} subscribers</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.newsletter.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Email or name..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($subscribers as $s)
        <div class="p-4 flex justify-between items-center">
            <div>
                <p class="font-medium text-gray-900">{{ $s->email }}</p>
                @if ($s->name)<p class="text-sm text-gray-500">{{ $s->name }}</p>@endif
                <p class="text-xs text-gray-400">{{ $s->subscribed_at?->format('M d, Y') }}</p>
            </div>
            <span class="text-xs {{ $s->is_active ? 'text-emerald-600' : 'text-gray-400' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No subscribers</div>
        @endforelse
    </div>
    @if ($subscribers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $subscribers->links() }}</div>
    @endif
</div>
@endsection
