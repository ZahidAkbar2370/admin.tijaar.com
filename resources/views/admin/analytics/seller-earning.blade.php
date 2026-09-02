@extends('admin.layouts.app')

@section('title', 'Seller Earning Report')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Seller Earning Report</h1>
    <p class="text-sm text-gray-500 mt-1">Gross sales, coupon discount, commission, and net earnings by seller</p>
</div>

@include('admin.analytics._date_filter', ['route' => route('admin.analytics.seller-earning'), 'period' => $period ?? 30, 'dateFrom' => $dateFrom ?? null, 'dateTo' => $dateTo ?? null, 'from' => $from, 'to' => $to])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Gross Sales</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totals['gross_sales'] ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Coupon Discount</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">−{{ number_format($totals['total_coupon_discount'] ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Total Commission</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($totals['total_commission'] ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Net (to sellers)</p>
        <p class="text-2xl font-bold text-primary mt-1">{{ number_format($totals['net_earnings'] ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Order lines</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totals['order_count'] ?? 0, 0) }}</p>
    </div>
</div>

@if(isset($byDayPaginator) && $byDayPaginator->total() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Net earnings by day</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80"><tr><th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Gross</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Coupon</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Commission</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Net</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($byDayPaginator as $r)
                <tr><td class="px-4 py-2">{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</td><td class="px-4 py-2 text-right">{{ number_format($r->gross ?? 0, 0) }}</td><td class="px-4 py-2 text-right text-emerald-600">−{{ number_format($r->coupon_discount ?? 0, 0) }}</td><td class="px-4 py-2 text-right text-amber-600">{{ number_format($r->commission ?? 0, 0) }}</td><td class="px-4 py-2 text-right font-medium text-primary">{{ number_format($r->net ?? 0, 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination-arrows', ['paginator' => $byDayPaginator])
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">By seller</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Seller</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Orders</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Gross (PKR)</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Coupon (PKR)</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Commission (PKR)</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Net (PKR)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($itemsPaginator ?? $items as $row)
                <tr class="table-row-hover">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $sellerNames->get($row->seller_id)->name ?? 'Seller #'.$row->seller_id }}</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-lg text-xs {{ $row->seller_type === 'private' ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-700' }}">{{ $row->seller_type ?? '—' }}</span></td>
                    <td class="px-4 py-3 text-right">{{ $row->order_count }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->gross_sales ?? 0, 0) }}</td>
                    <td class="px-4 py-3 text-right text-emerald-600">−{{ number_format($row->coupon_discount ?? 0, 0) }}</td>
                    <td class="px-4 py-3 text-right text-amber-600">{{ number_format($row->total_commission ?? 0, 0) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-primary">{{ number_format($row->net_earnings ?? 0, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No data in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($itemsPaginator) && $itemsPaginator->hasPages())
    @include('admin.partials.pagination-arrows', ['paginator' => $itemsPaginator])
    @endif
</div>
@endsection
