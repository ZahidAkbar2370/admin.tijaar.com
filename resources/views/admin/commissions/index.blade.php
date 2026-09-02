@extends('admin.layouts.app')

@section('title', 'Commission Rules')

@section('admin-content')
@include('admin.commission.partials.nav')

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Advanced Commission Rules</h1>
        <p class="text-sm text-gray-500 mt-1">Per-seller and per-category overrides. Default business seller commission is under <a href="{{ route('admin.seller-settings.index') }}" class="text-primary hover:underline">Customer as Seller</a>; buyer checkout fees under <a href="{{ route('admin.people-settings.index', ['tab' => 'customer']) }}" class="text-primary hover:underline">Customer as Buyer</a>; customer-seller order deductions under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline">Customer as Seller</a>. Priority: seller → seller type → category → global.</p>
    </div>
    <a href="{{ route('admin.commissions.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Rule
    </a>
</div>

@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Scope</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Value</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Priority</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($commissions as $c)
                <tr class="table-row-hover">
                    <td class="px-6 py-4 text-sm">
                        @if ($c->scope_type === 'global')
                            <span class="font-medium">Global</span>
                        @elseif ($c->scope_type === 'category')
                            <span class="text-gray-600">Category:</span> {{ $c->category?->name ?? '#' . $c->scope_id }}
                        @elseif ($c->scope_type === 'seller_type')
                            <span class="text-gray-600">Seller type:</span> {{ ucfirst($c->seller_type ?? '—') }}
                        @else
                            <span class="text-gray-600">Seller:</span> #{{ $c->scope_id }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($c->commission_type) }}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        @if ($c->commission_type === 'percentage')
                            {{ number_format($c->value, 1) }}%
                        @else
                            {{ number_format($c->value, 0) }} (fixed)
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $c->priority }}</td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST" action="{{ route('admin.commissions.destroy', $c) }}" class="inline" onsubmit="return sweetConfirm(event, 'Remove this commission rule? This cannot be undone.', 'Remove rule?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-500">No commission rules. Add a global default first.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
