@extends('admin.layouts.app')

@section('title', 'Products')

@section('admin-content')
<div x-data="{
    columns: {
        id: { label: 'ID', visible: true },
        name: { label: 'Product', visible: true },
        store: { label: 'Store', visible: true },
        category: { label: 'Category', visible: true },
        price: { label: 'Price', visible: true },
        status: { label: 'Status', visible: true },
        actions: { label: 'Actions', visible: true }
    },
    showColumnMenu: false
}">

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <p class="text-sm text-gray-500 mt-1">Manage all products</p>
    </div>
    <a href="{{ route('admin.products.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export CSV
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.products.index') }}" id="product-filters" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="space-y-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                    <div class="relative">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                    </div>
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Category</label>
                    <select name="category_id" onchange="document.getElementById('product-filters').submit()" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Subcategory</label>
                    <select name="subcategory_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All</option>
                        @foreach ($subcategories as $sc)
                            <option value="{{ $sc->id }}" {{ request('subcategory_id') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Seller / Store</label>
                    <select name="store_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All</option>
                        @foreach ($stores as $s)
                            <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">Filter</button>
                @if (request()->hasAny(['search', 'status', 'category_id', 'subcategory_id', 'store_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
                @endif
            <div class="relative ml-auto">
                <button type="button" @click="showColumnMenu = !showColumnMenu" class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Columns
                </button>
                <div x-show="showColumnMenu" @click.away="showColumnMenu = false" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2" style="z-index: 9999;">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Toggle Columns</p>
                    <template x-for="(col, key) in columns" :key="key">
                        <label class="column-toggle-item flex items-center gap-3 px-4 py-2 cursor-pointer transition">
                            <input type="checkbox" x-model="col.visible" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                            <span class="text-sm text-gray-700" x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th x-show="columns.id.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th x-show="columns.name.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Product</th>
                    <th x-show="columns.store.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Store</th>
                    <th x-show="columns.category.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Category</th>
                    <th x-show="columns.price.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Price</th>
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $p)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $p->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @php $mainImageUrl = $p->getMainImageUrl(); @endphp
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden relative">
                                @if ($mainImageUrl)
                                    <img src="{{ $mainImageUrl }}" alt="" class="w-12 h-12 rounded-lg object-cover absolute inset-0" onerror="this.style.display='none'; this.nextElementSibling && this.nextElementSibling.classList.remove('hidden');" />
                                    <span class="hidden w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                                    </span>
                                @else
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $p->name }}</p>
                                <p class="text-xs text-gray-400">{{ $p->sku ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td x-show="columns.store.visible" class="px-6 py-4 text-sm text-gray-600">{{ $p->store?->name ?? '—' }}</td>
                    <td x-show="columns.category.visible" class="px-6 py-4 text-sm text-gray-500">{{ $p->category?->name ?? '—' }}</td>
                    <td x-show="columns.price.visible" class="px-6 py-4 text-sm font-medium text-gray-900">{{ number_format($p->price, 0) }}</td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @php
                            $statusColors = [
                                'draft' => 'bg-gray-100 text-gray-600',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'published' => 'bg-emerald-100 text-emerald-700',
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                            ];
                            $statusClass = $statusColors[$p->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $p->status }}</span>
                    </td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.products.show', $p) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg transition" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </a>
                            @if ($p->status === 'pending')
                            <form method="POST" action="{{ route('admin.products.approve', $p) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.products.reject', $p) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition">Reject</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="text-gray-500 font-medium">No products found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $products->links() }}
    </div>
    @endif
</div>

</div>
@endsection
