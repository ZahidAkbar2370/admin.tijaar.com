@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Reviews</h1>
        <p class="text-sm text-gray-500 mt-1">Moderate product and store reviews. Only <strong>approved</strong> reviews are shown on the customer website.</p>
    </div>
</div>

<div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-900">
    Pending reviews stay hidden on product pages until you approve them. Rejected reviews never appear on the frontend.
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Review text, user..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="" {{ request()->has('status') && request('status') === '' ? 'selected' : '' }}>All</option>
                    <option value="pending" {{ (!request()->has('status') || request('status') === 'pending') ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($reviews as $r)
        <div class="p-6">
            <div class="flex justify-between items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-gray-900">{{ $r->user?->name }}</span>
                        <span class="text-gray-500 text-sm">{{ $r->user?->email }}</span>
                        @if ($r->is_verified_purchase)
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Verified</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        @for ($i = 1; $i <= 5; $i++)
                        <span class="text-amber-400">{{ $i <= $r->rating ? '★' : '☆' }}</span>
                        @endfor
                        <span class="text-sm text-gray-500">{{ $r->rating }}/5</span>
                    </div>
                    @if ($r->title)<p class="font-medium text-gray-900 mb-1">{{ $r->title }}</p>@endif
                    <p class="text-gray-600 text-sm">{{ Str::limit($r->body, 200) }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        @if ($r->reviewable)
                            @if ($r->reviewable_type === 'App\Models\Product')
                                <a href="{{ route('admin.products.show', $r->reviewable) }}" class="text-primary hover:underline">{{ $r->reviewable->name }}</a>
                                @if ($r->reviewable->slug)<span class="text-gray-400"> ({{ $r->reviewable->slug }})</span>@endif
                            @elseif ($r->reviewable_type === 'App\Models\Store')
                                <a href="{{ route('admin.stores.show', $r->reviewable) }}" class="text-primary hover:underline">{{ $r->reviewable->name }}</a>
                            @else
                                {{ class_basename($r->reviewable_type ?? '') }} #{{ $r->reviewable_id }}
                            @endif
                        @else
                            {{ $r->reviewable_type ? \Illuminate\Support\Str::afterLast($r->reviewable_type, '\\') : 'Item' }} #{{ $r->reviewable_id }}
                        @endif
                        • {{ $r->created_at->format('M d, Y') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium
                        @if($r->status === 'approved') bg-emerald-100 text-emerald-700
                        @elseif($r->status === 'rejected') bg-red-100 text-red-700
                        @else bg-amber-100 text-amber-700 @endif">{{ $r->status }}</span>
                    @if ($r->status === 'pending')
                    <form method="POST" action="{{ route('admin.reviews.approve', $r) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.reject', $r) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg">Reject</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No reviews</div>
        @endforelse
    </div>
    @if ($reviews->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
