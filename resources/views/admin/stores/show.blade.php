@extends('admin.layouts.app')

@section('title', 'Store: ' . $store->name)

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Stores
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6">
        <div class="flex items-start gap-6">
            @if ($store->logo)
                <img src="{{ \App\Support\UploadHelper::url($store->logo) }}" alt="" class="w-20 h-20 rounded-xl object-cover" />
            @else
                <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $store->name }}</h1>
                <p class="text-sm text-gray-500">{{ $store->slug }}</p>
                <div class="mt-2 flex items-center gap-2">
                    @if ($store->is_active)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Inactive</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Store Details</h3>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">Description</dt><dd class="text-gray-900">{{ $store->description ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Address</dt><dd class="text-gray-900">{{ $store->address ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">City</dt><dd class="text-gray-900">{{ $store->city ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Country</dt><dd class="text-gray-900">{{ $store->country ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Phone</dt><dd class="text-gray-900">{{ $store->phone ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $store->email ?: '—' }}</dd></div>
                </dl>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Seller</h3>
                @if ($store->seller)
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">Name</dt><dd class="text-gray-900">{{ $store->seller->user?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $store->seller->user?->email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Status</dt><dd><span class="px-2 py-1 rounded text-xs font-medium bg-gray-100">{{ $store->seller->status }}</span></dd></div>
                </dl>
                <a href="{{ route('admin.sellers.show', $store->seller->user) }}" class="mt-2 text-primary hover:underline text-sm font-medium">View Seller →</a>
                @else
                <p class="text-gray-500">No seller linked</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
