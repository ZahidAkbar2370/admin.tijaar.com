@extends('admin.layouts.app')

@section('title', 'Conversation')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.conversations.index') }}" class="text-[#1790d7] text-sm hover:underline">← Back</a>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
        <h2 class="font-semibold text-gray-900">{{ $conversation->user?->name }} (Buyer) ↔ {{ $conversation->seller?->name }} (Seller)</h2>
        @if ($conversation->product)
        <p class="text-sm text-gray-500 mt-1">Re: {{ $conversation->product->name }}</p>
        @endif
    </div>
    <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
        @foreach ($conversation->messages->sortBy('created_at') as $m)
        <div class="{{ $m->user_id === $conversation->user_id ? 'pl-4 border-l-2 border-[#1790d7]' : 'pr-4 border-r-2 border-amber-300' }}">
            <p class="text-xs text-gray-500">{{ $m->user?->name }} · {{ $m->created_at->format('M d, H:i') }}</p>
            <p class="text-gray-900 mt-1">{{ $m->body }}</p>
        </div>
        @endforeach
    </div>
</div>
@if ($reported->count() > 0)
<div class="mt-6 p-6 bg-amber-50 rounded-2xl border border-amber-200">
    <h3 class="font-semibold text-amber-800 mb-2">Reports</h3>
    @foreach ($reported as $r)
    <p class="text-sm text-amber-700">{{ $r->user?->name }}: {{ $r->reason ?? 'No reason' }}</p>
    @endforeach
</div>
@endif
@endsection
