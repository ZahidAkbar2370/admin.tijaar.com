@extends('admin.layouts.app')

@section('title', 'Reported Conversations')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.conversations.index') }}" class="text-[#1790d7] text-sm hover:underline">← Back to Conversations</a>
</div>
<h1 class="text-2xl font-bold text-gray-900 mb-6">Reported Conversations</h1>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-100">
    @forelse ($reports as $r)
    <a href="{{ route('admin.conversations.show', $r->conversation_id) }}" class="block p-6 hover:bg-gray-50/50">
        <p class="font-medium text-gray-900">Report by {{ $r->user?->name }}</p>
        <p class="text-sm text-gray-600">{{ $r->reason ?? 'No reason' }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $r->created_at->diffForHumans() }}</p>
    </a>
    @empty
    <div class="p-16 text-center text-gray-500">No reported conversations</div>
    @endforelse
</div>
@if ($reports->hasPages())
<div class="px-6 py-4 border-t border-gray-100">{{ $reports->links() }}</div>
@endif
@endsection
