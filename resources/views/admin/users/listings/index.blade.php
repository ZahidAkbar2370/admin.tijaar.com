@extends('admin.layouts.app')
@section('title', 'Listings — Customer #' . $user->id)
@section('admin-content')
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-lg font-bold text-gray-900">Listings</h1>
        <a href="{{ route('admin.users.listings.create', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-dark">Create listing</a>
    </div>
    <form method="GET" class="p-5 border-b border-gray-100 flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU…" class="px-4 py-2 rounded-xl border border-gray-200 text-sm flex-1 min-w-[180px]">
        <select name="status" class="px-4 py-2 rounded-xl border border-gray-200 text-sm">
            <option value="">All statuses</option>
            @foreach (['draft', 'pending', 'published', 'rejected', 'unpublished', 'removed'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 rounded-xl text-sm font-medium">Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Price</th>
                    <th class="px-4 py-3 text-left">Qty</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($listings as $listing)
                <tr class="{{ $listing->trashed() ? 'opacity-60' : '' }}">
                    <td class="px-4 py-3 font-medium">{{ \Illuminate\Support\Str::limit($listing->name, 40) }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $listing->sku }}</td>
                    <td class="px-4 py-3">{{ $listing->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ number_format((float) $listing->price, 0) }}</td>
                    <td class="px-4 py-3">{{ $listing->quantity }}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-lg text-xs font-medium bg-gray-100">{{ ucfirst($listing->status) }}</span></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap justify-end gap-1">
                            @if (!$listing->trashed())
                                <a href="{{ route('admin.users.listings.edit', [$user, $listing]) }}" class="px-2 py-1 text-xs rounded-lg bg-primary/10 text-primary font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.users.listings.status', [$user, $listing]) }}" class="inline">@csrf
                                    <input type="hidden" name="status" value="{{ $listing->status === 'published' ? 'draft' : 'published' }}">
                                    <button type="submit" class="px-2 py-1 text-xs rounded-lg bg-emerald-50 text-emerald-700 font-medium">{{ $listing->status === 'published' ? 'Unpublish' : 'Publish' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.listings.destroy', [$user, $listing]) }}" class="inline" onsubmit="return confirm('Remove this listing?');">@csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs rounded-lg bg-red-50 text-red-700 font-medium">Delete</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.listings.restore', [$user, $listing->id]) }}" class="inline">@csrf
                                    <button type="submit" class="px-2 py-1 text-xs rounded-lg bg-sky-50 text-sky-700 font-medium">Restore</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.products.show', $listing) }}" class="px-2 py-1 text-xs rounded-lg bg-gray-100 text-gray-700 font-medium">Catalog</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No listings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($listings->hasPages())<div class="p-4 border-t border-gray-100">{{ $listings->links() }}</div>@endif
</section>
@endsection
