@extends('admin.layouts.app')
@section('title', 'Products — Seller #' . $user->id)
@section('admin-content')
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><h1 class="text-xl font-bold text-gray-900">Products</h1><p class="text-sm text-gray-500 mt-1">Manage business store products for this seller.</p></div>
        <a href="{{ route('admin.sellers.products.create', $user) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-medium hover:bg-amber-600">Create product</a>
    </div>
    <form method="GET" class="p-5 border-b border-gray-100 flex flex-wrap gap-3 bg-gray-50/50">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU…" class="px-4 py-2 rounded-xl border border-gray-200 text-sm flex-1 min-w-[200px]">
        <select name="status" class="px-4 py-2 rounded-xl border border-gray-200 text-sm"><option value="">All statuses</option>@foreach (['draft','pending','published','rejected'] as $st)<option value="{{ $st }}" @selected(request('status')===$st)>{{ ucfirst($st) }}</option>@endforeach</select>
        <button type="submit" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium">Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Product</th><th class="px-4 py-3 text-left">SKU</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Price</th><th class="px-4 py-3 text-left">Qty</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y">@forelse ($products as $product)<tr><td class="px-4 py-3 font-medium">{{ \Illuminate\Support\Str::limit($product->name, 45) }}</td><td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $product->sku }}</td><td class="px-4 py-3">{{ $product->category?->name ?? '—' }}</td><td class="px-4 py-3">{{ number_format((float)$product->price, 0) }}</td><td class="px-4 py-3">{{ $product->quantity }}</td><td class="px-4 py-3"><span class="px-2 py-0.5 rounded-lg text-xs bg-gray-100">{{ ucfirst($product->status) }}</span></td><td class="px-4 py-3"><div class="flex justify-end gap-1 flex-wrap"><a href="{{ route('admin.sellers.products.edit', [$user, $product]) }}" class="px-2 py-1 text-xs rounded-lg bg-amber-50 text-amber-800 font-medium">Edit</a>@if(!$product->trashed())<form method="POST" action="{{ route('admin.sellers.products.status', [$user, $product]) }}" class="inline">@csrf<input type="hidden" name="status" value="{{ $product->status === 'published' ? 'draft' : 'published' }}"><button type="submit" class="px-2 py-1 text-xs rounded-lg bg-emerald-50 text-emerald-700 font-medium">{{ $product->status === 'published' ? 'Unpublish' : 'Publish' }}</button></form><form method="POST" action="{{ route('admin.sellers.products.destroy', [$user, $product]) }}" class="inline" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button type="submit" class="px-2 py-1 text-xs rounded-lg bg-red-50 text-red-700 font-medium">Delete</button></form>@endif<a href="{{ route('admin.products.show', $product) }}" class="px-2 py-1 text-xs rounded-lg bg-gray-100 font-medium">Catalog</a></div></td></tr>@empty<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No products found.</td></tr>@endforelse</tbody></table>
    </div>
    @if ($products->hasPages())<div class="p-4 border-t">{{ $products->links() }}</div>@endif
</section>
@endsection
