@extends('admin.layouts.app')

@section('title', 'Low Stock Report')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Low Stock Report</h1>
    <p class="text-sm text-gray-500 mt-1">Products below stock threshold</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.inventory.low-stock') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 font-semibold text-gray-700">Product</th>
                    <th class="text-left px-6 py-3 font-semibold text-gray-700">SKU</th>
                    <th class="text-left px-6 py-3 font-semibold text-gray-700">Seller</th>
                    <th class="text-right px-6 py-3 font-semibold text-gray-700">Qty</th>
                    <th class="text-right px-6 py-3 font-semibold text-gray-700">Threshold</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $p)
                <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.products.show', $p) }}" class="font-medium text-primary hover:underline">{{ $p->name }}</a>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $p->sku ?? '—' }}</td>
                    <td class="px-6 py-4">{{ $p->store?->seller?->user?->name ?? $p->sellerUser?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-right font-semibold text-amber-600">{{ $p->quantity }}</td>
                    <td class="px-6 py-4 text-right text-gray-500">{{ $p->low_stock_threshold ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-500">No low stock products</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>
    @endif
</div>
@endsection
