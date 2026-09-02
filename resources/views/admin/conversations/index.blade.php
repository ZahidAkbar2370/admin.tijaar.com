@extends('admin.layouts.app')

@section('title', 'Conversations')

@section('admin-content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Conversations</h1>
        <p class="text-sm text-gray-500 mt-1">Customer-seller messages</p>
    </div>
    <a href="{{ route('admin.conversations.reported') }}" class="text-[#1790d7] font-medium hover:underline text-sm">Reported conversations →</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.conversations.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="User name, email..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($conversations as $c)
        <a href="{{ route('admin.conversations.show', $c) }}" class="block p-6 hover:bg-gray-50/50 transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-gray-900">{{ $c->user?->name }} ↔ {{ $c->seller?->name }}</p>
                    <p class="text-sm text-gray-500">{{ $c->user?->email }} / {{ $c->seller?->email }}</p>
                    @if ($c->product)
                    <p class="text-xs text-gray-400 mt-1">Re: {{ $c->product->name }}</p>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ $c->last_message_at?->diffForHumans() ?? $c->updated_at->diffForHumans() }}</span>
            </div>
        </a>
        @empty
        <div class="p-16 text-center text-gray-500">No conversations</div>
        @endforelse
    </div>
    @if ($conversations->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection
