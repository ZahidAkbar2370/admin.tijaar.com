@extends('admin.layouts.app')

@section('title', 'Commission Report')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Commission Report</h1>
    <p class="text-sm text-gray-500 mt-1">Platform commission from orders and active rules</p>
</div>

@include('admin.analytics._date_filter', ['route' => route('admin.analytics.commission'), 'period' => $period ?? 30, 'dateFrom' => $dateFrom ?? null, 'dateTo' => $dateTo ?? null, 'from' => $from, 'to' => $to])

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Total commission (period)</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($commissionTotal ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">By day (rows)</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ isset($byDay) ? $byDay->count() : 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Active rules</p>
        <p class="text-2xl font-bold text-primary mt-1">{{ isset($rules) ? $rules->count() : 0 }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Commission by day</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 sticky top-0"><tr><th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Commission (PKR)</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($byDayPaginator ?? $byDay ?? [] as $r)
                    <tr><td class="px-4 py-2">{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</td><td class="px-4 py-2 text-right font-medium text-amber-600">{{ number_format($r->commission ?? 0, 0) }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-gray-500">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($byDayPaginator) && $byDayPaginator->hasPages())
        @include('admin.partials.pagination-arrows', ['paginator' => $byDayPaginator])
        @endif
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Commission by seller</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 sticky top-0"><tr><th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Seller ID</th><th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Type</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Commission (PKR)</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bySellerPaginator ?? $bySeller ?? [] as $r)
                    <tr><td class="px-4 py-2">#{{ $r->seller_id }}</td><td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs bg-gray-100">{{ $r->seller_type ?? '—' }}</span></td><td class="px-4 py-2 text-right font-medium text-amber-600">{{ number_format($r->commission ?? 0, 0) }}</td></tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($bySellerPaginator) && $bySellerPaginator->hasPages())
        @include('admin.partials.pagination-arrows', ['paginator' => $bySellerPaginator])
        @endif
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Active commission rules</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Scope</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Seller type</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Value</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Priority</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rules ?? [] as $r)
                <tr class="table-row-hover">
                    <td class="px-4 py-3">{{ $r->scope_type }} @if($r->scope_id) #{{ $r->scope_id }} @endif</td>
                    <td class="px-4 py-3">{{ $r->seller_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ $r->commission_type }}</td>
                    <td class="px-4 py-3 text-right font-medium">@if($r->commission_type === 'percentage'){{ $r->value }}% @else {{ number_format($r->value, 2) }} @endif</td>
                    <td class="px-4 py-3 text-right">{{ $r->priority }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No commission rules</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
