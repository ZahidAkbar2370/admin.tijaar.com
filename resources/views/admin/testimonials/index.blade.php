@extends('admin.layouts.app')

@section('title', 'Testimonials')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Testimonials</h1>
        <p class="text-sm text-gray-500 mt-1">Manage customer testimonials shown on the site</p>
    </div>
    <a href="{{ route('admin.testimonials.create') }}" class="px-4 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">Add Testimonial</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.testimonials.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, company, or quote..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Search</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($testimonials as $t)
        <div class="p-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                @if ($t->avatar)
                    <img src="{{ \App\Support\UploadHelper::url($t->avatar) }}" alt="" class="w-12 h-12 rounded-full object-cover shrink-0" />
                @else
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold shrink-0">{{ Str::limit($t->name, 1) }}</div>
                @endif
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $t->name }}</p>
                    <p class="text-sm text-gray-500">{{ $t->role }}{{ $t->company ? ' · ' . $t->company : '' }}</p>
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ Str::limit(strip_tags($t->content), 120) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($t->rating)
                    <span class="text-amber-500 text-sm">{{ $t->rating }}/5</span>
                @endif
                <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $t->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $t->is_active ? 'Active' : 'Hidden' }}</span>
                <a href="{{ route('admin.testimonials.edit', $t) }}" class="px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg">Edit</a>
                <form method="POST" action="{{ route('admin.testimonials.destroy', $t) }}" onsubmit="return sweetConfirm(event, 'Delete this testimonial? This cannot be undone.', 'Delete testimonial?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No testimonials yet. Add one to display on your site.</div>
        @endforelse
    </div>
    @if ($testimonials->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $testimonials->links() }}</div>
    @endif
</div>
@endsection
