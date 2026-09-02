@extends('admin.layouts.app')

@section('title', 'Product: ' . $product->name)

@section('admin-content')
<div class="w-full min-w-0">
    <a href="{{ route('admin.products.index', request()->query()) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Products
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">#{{ $product->id }} · {{ $product->sku ?? 'No SKU' }}</p>
                </div>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-600',
                        'pending' => 'bg-amber-100 text-amber-700',
                        'published' => 'bg-emerald-100 text-emerald-700',
                        'rejected' => 'bg-red-100 text-red-700',
                    ];
                    $statusClass = $statusColors[$product->status] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex px-4 py-2 rounded-xl text-sm font-semibold {{ $statusClass }}">{{ ucfirst($product->status) }}</span>
                    @if ($product->status === 'pending')
                    <form method="POST" action="{{ route('admin.products.approve', $product) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded-xl border border-emerald-200 transition">Approve & Publish</button>
                    </form>
                    <form method="POST" action="{{ route('admin.products.reject', $product) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl border border-red-200 transition">Reject</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6 grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Details</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Price</dt><dd class="font-medium">{{ number_format((float) ($product->price ?? 0), 0) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Quantity</dt><dd class="font-medium">{{ $product->quantity }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Condition</dt><dd class="font-medium">{{ ucfirst($product->condition ?? '—') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd class="font-medium">{{ $product->category?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Brand</dt><dd class="font-medium">{{ $product->brand?->name ?? '—' }}</dd></div>
                </dl>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Seller</h2>
                <dl class="space-y-2 text-sm">
                    @if ($product->store)
                        <div><dt class="text-gray-500">Store</dt><dd class="font-medium">{{ $product->store->name }}</dd></div>
                        <div><dt class="text-gray-500">Seller</dt><dd class="font-medium">{{ $product->store->seller?->user?->name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Type</dt><dd class="font-medium">Business</dd></div>
                    @else
                        <div><dt class="text-gray-500">Private Seller</dt><dd class="font-medium">{{ $product->sellerUser?->name ?? '—' }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        @if ($product->description)
        <div class="px-6 pb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Description</h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $product->description }}</p>
        </div>
        @endif

        <div class="px-6 pb-6 border-t border-gray-100 pt-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">SEO (Meta Tags)</h2>
            <p class="text-xs text-gray-500 mb-4">Auto-filled from product name and description. Edit as needed, then save.</p>
            @if (session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ url('/admin/products/' . $product->id . '/seo') }}" class="space-y-4 max-w-2xl">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $seo['meta_title'] ?? '') }}"
                           placeholder="{{ $product->name }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    <p class="text-xs text-gray-500 mt-1">From product name if not set.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta description</label>
                    <textarea name="meta_description" rows="3"
                              placeholder="Short summary for search results"
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('meta_description', $seo['meta_description'] ?? '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">From short or full description if not set.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta keywords</label>
                    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seo['meta_keywords'] ?? '') }}"
                           placeholder="{{ $product->name }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    <p class="text-xs text-gray-500 mt-1">From product name if not set.</p>
                </div>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-xl hover:bg-primary/90 transition">
                    Save SEO
                </button>
            </form>
        </div>

        @if ($product->expires_at)
        <div class="px-6 pb-6">
            <p class="text-xs text-gray-500">Expires: {{ $product->expires_at?->format('M d, Y') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
