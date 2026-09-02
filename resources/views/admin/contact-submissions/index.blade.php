@extends('admin.layouts.app')

@section('title', 'Contact Submissions')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Contact Submissions</h1>
    <p class="text-sm text-gray-500 mt-1">Messages from contact form</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.contact-submissions.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, message..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($submissions as $s)
        <a href="{{ route('admin.contact-submissions.show', $s) }}" class="block p-4 hover:bg-gray-50 {{ $s->status === 'new' ? 'bg-blue-50/50' : '' }}">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium text-gray-900">{{ $s->name }} &lt;{{ $s->email }}&gt;</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $s->subject ?? 'No subject' }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ Str::limit($s->message, 80) }}</p>
                </div>
                @php $createdAtPk = $s->created_at->copy()->setTimezone('Asia/Karachi'); @endphp
                <span class="text-xs text-gray-400" title="{{ $createdAtPk->format('M d, Y g:i A') }} (PKT)">{{ $createdAtPk->format('M d, Y g:i A') }}</span>
            </div>
        </a>
        @empty
        <div class="p-16 text-center text-gray-500">No submissions</div>
        @endforelse
    </div>
    @if ($submissions->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $submissions->links() }}</div>
    @endif
</div>
@endsection
