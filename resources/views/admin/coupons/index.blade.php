@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Coupons</h1>
        <p class="text-sm text-gray-500 mt-1">Platform and store coupons</p>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Coupon
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.coupons.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search Code</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Coupon code..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Scope</label>
                <select name="scope" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="platform" {{ request('scope') === 'platform' ? 'selected' : '' }}>Platform</option>
                    <option value="store" {{ request('scope') === 'store' ? 'selected' : '' }}>Store</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Value</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Scope</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Used</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Valid</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($coupons as $c)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 font-mono font-semibold">{{ $c->code }}</td>
                    <td class="px-6 py-4 text-sm">{{ ucfirst($c->type) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($c->type === 'percentage'){{ $c->value }}%
                        @else {{ number_format($c->value, 2) }} @endif
                    </td>
                    <td class="px-6 py-4 text-sm">{{ ucfirst($c->scope) }} @if($c->store) ({{ $c->store->name }}) @endif</td>
                    <td class="px-6 py-4 text-sm">{{ $c->used_count }}{{ $c->max_uses ? '/' . $c->max_uses : '' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($c->is_active)
                            <span class="text-emerald-600">Active</span>
                        @else
                            <span class="text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.coupons.edit', $c) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition">Edit</a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $c) }}" class="inline" onsubmit="return sweetConfirm(event, 'Delete this coupon? This cannot be undone.', 'Delete coupon?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center text-gray-500">No coupons</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($coupons->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection
