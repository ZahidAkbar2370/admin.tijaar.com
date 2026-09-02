@extends('admin.layouts.app')

@section('title', 'Dispute ' . $dispute->dispute_number)

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800">{{ session('error') }}</div>
@endif

<div class="mb-6">
    <a href="{{ route('admin.disputes.index') }}" class="text-[#1790d7] text-sm hover:underline">← Back</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-100">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $dispute->dispute_number }}</h1>
                <p class="text-gray-500">Order #{{ $dispute->order?->order_number }} · {{ $dispute->user?->name }} · {{ $dispute->type }}</p>
            </div>
            <span class="px-4 py-2 rounded-xl text-sm font-medium
                @if($dispute->status === 'resolved') bg-emerald-100 text-emerald-700
                @elseif($dispute->status === 'rejected') bg-red-100 text-red-700
                @else bg-amber-100 text-amber-700 @endif">{{ $dispute->status }}</span>
        </div>
        <p class="mt-4 text-gray-700">{{ $dispute->description }}</p>
    </div>

    <div class="p-6 border-b border-gray-100 space-y-4">
        <h3 class="font-semibold text-gray-900">Messages</h3>
        @foreach ($dispute->messages->sortBy('created_at') as $m)
        <div class="p-4 rounded-xl {{ $m->is_admin ? 'bg-blue-50 border border-blue-100' : 'bg-gray-50' }}">
            <p class="text-xs text-gray-500">{{ $m->user?->name }}{{ $m->is_admin ? ' (Admin)' : '' }} · {{ $m->created_at->format('M d, H:i') }}</p>
            <p class="text-gray-900 mt-1">{{ $m->body }}</p>
        </div>
        @endforeach
    </div>

    @if (!in_array($dispute->status, ['resolved', 'rejected', 'refunded']))
    <div class="p-6 space-y-4">
        <form method="POST" action="{{ route('admin.disputes.add-message', $dispute) }}" class="flex gap-2">
            @csrf
            <input type="text" name="body" required placeholder="Add message..." class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            <button type="submit" class="px-4 py-2.5 bg-[#1790d7] text-white rounded-xl text-sm font-medium">Send</button>
        </form>
        <form method="POST" action="{{ route('admin.disputes.arbitrate', $dispute) }}" class="flex flex-wrap gap-3">
            @csrf
            <input type="hidden" name="action" value="resolve" />
            <input type="text" name="notes" placeholder="Resolution notes (optional)" class="flex-1 min-w-[200px] px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            <button type="submit" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium">Resolve</button>
        </form>
        <form method="POST" action="{{ route('admin.disputes.arbitrate', $dispute) }}" class="inline">
            @csrf
            <input type="hidden" name="action" value="reject" />
            <input type="text" name="notes" placeholder="Rejection notes (optional)" class="inline-block w-64 px-4 py-2.5 rounded-xl border border-gray-200 text-sm mr-2" />
            <button type="submit" class="px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-medium">Reject</button>
        </form>
    </div>
    @endif

    @if ($dispute->status === 'resolved')
    <div class="p-6 border-t border-gray-100">
        <form method="POST" action="{{ route('admin.disputes.process-refund', $dispute) }}" onsubmit="return sweetConfirm(event, 'Process refund for this order?', 'Process refund?');">
            @csrf
            <button type="submit" class="px-4 py-2.5 bg-amber-600 text-white rounded-xl text-sm font-medium">Process Refund</button>
        </form>
    </div>
    @endif
</div>
@endsection
