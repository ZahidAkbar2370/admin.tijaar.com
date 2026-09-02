@extends('admin.layouts.app')

@section('title', 'FAQs')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">FAQs</h1>
        <p class="text-sm text-gray-500 mt-1">Manage frequently asked questions</p>
    </div>
    <a href="{{ route('admin.faqs.create') }}" class="px-4 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Create FAQ</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.faqs.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Question or answer..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($faqs as $f)
        <div class="p-4 flex justify-between items-start">
            <div>
                <p class="font-medium text-gray-900">{{ $f->question }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ Str::limit(strip_tags($f->answer), 100) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.faqs.edit', $f) }}" class="px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg">Edit</a>
                <form method="POST" action="{{ route('admin.faqs.destroy', $f) }}" onsubmit="return sweetConfirm(event, 'Delete this FAQ? This cannot be undone.', 'Delete FAQ?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No FAQs</div>
        @endforelse
    </div>
    @if ($faqs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $faqs->links() }}</div>
    @endif
</div>
@endsection
