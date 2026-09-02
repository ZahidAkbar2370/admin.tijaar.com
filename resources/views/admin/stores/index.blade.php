@extends('admin.layouts.app')

@section('title', 'Stores')

@section('admin-content')
<div x-data="{
    columns: {
        id: { label: 'ID', visible: true },
        name: { label: 'Store', visible: true },
        seller: { label: 'Seller', visible: true },
        country: { label: 'Country', visible: true },
        status: { label: 'Status', visible: true },
        actions: { label: 'Actions', visible: true }
    },
    showColumnMenu: false
}">

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stores</h1>
        <p class="text-sm text-gray-500 mt-1">Manage seller stores</p>
    </div>
    <a href="{{ route('admin.stores.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export CSV
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.stores.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Store or seller..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">Filter</button>
            @if (request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.stores.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
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
                    <th x-show="columns.name.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Store</th>
                    <th x-show="columns.seller.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Seller</th>
                    <th x-show="columns.country.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Country</th>
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($stores as $s)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $s->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if ($s->logo)
                                <img src="{{ \App\Support\UploadHelper::url($s->logo) }}" alt="" class="w-10 h-10 rounded-lg object-cover" />
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-900">{{ $s->name }}</p>
                                <p class="text-xs text-gray-400">{{ $s->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td x-show="columns.seller.visible" class="px-6 py-4 text-sm text-gray-600">{{ $s->seller?->user?->name ?? '—' }}</td>
                    <td x-show="columns.country.visible" class="px-6 py-4 text-sm text-gray-500">{{ $s->country ?? '—' }}</td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @if ($s->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <a href="{{ route('admin.stores.show', $s) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <p class="text-gray-500 font-medium">No stores found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($stores->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $stores->links() }}
    </div>
    @endif
</div>

</div>
@endsection
