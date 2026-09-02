@extends('admin.layouts.app')

@section('title', 'Sales Report')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Sales Report</h1>
    <p class="text-sm text-gray-500 mt-1">Order revenue, package earnings, and date-wise breakdown</p>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" action="{{ route('admin.analytics.sales') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Quick period</label>
                <select name="period" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="7" {{ ($period ?? 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ ($period ?? 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ ($period ?? 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">To</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Apply</button>
        </div>
    </form>
    <p class="text-xs text-gray-500 mt-2">Showing data from {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Order Revenue</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['order_revenue'] ?? 0, 0) }} PKR</p>
        <p class="text-xs text-gray-400 mt-1">Paid orders only</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Package Earnings</p>
        <p class="text-2xl font-bold text-primary mt-1">{{ number_format($summary['package_earnings'] ?? 0, 0) }} PKR</p>
        <p class="text-xs text-gray-400 mt-1">Promotion purchases</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Total Paid Orders</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_paid_orders'] ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">In selected period</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Avg Order Value</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['avg_order_value'] ?? 0, 0) }} PKR</p>
        <p class="text-xs text-gray-400 mt-1">Per paid order</p>
    </div>
</div>

{{-- Chart: Revenue by day --}}
@if(isset($ordersByDay) && $ordersByDay->isNotEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Revenue by day</h3>
    <div class="h-64">
        <canvas id="salesChart" width="400" height="200"></canvas>
    </div>
</div>
@endif

{{-- Date-wise & monthly breakdown --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Date-wise summary</h3>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Revenue (PKR)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ordersByDayPaginator ?? $ordersByDay ?? [] as $row)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 text-gray-700">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ $row->count }}</td>
                        <td class="px-4 py-3 text-right font-medium text-primary">{{ number_format($row->revenue ?? 0, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No data in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($ordersByDayPaginator) && $ordersByDayPaginator->hasPages())
        @include('admin.partials.pagination-arrows', ['paginator' => $ordersByDayPaginator])
        @endif
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Monthly summary</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Revenue (PKR)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                    @forelse($ordersByMonthPaginator ?? $ordersByMonth ?? [] as $row)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 text-gray-700">{{ $months[$row->month] ?? $row->month }} {{ $row->year }}</td>
                        <td class="px-4 py-3 text-right">{{ $row->count }}</td>
                        <td class="px-4 py-3 text-right font-medium text-primary">{{ number_format($row->revenue ?? 0, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No data in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($ordersByMonthPaginator) && $ordersByMonthPaginator->hasPages())
        @include('admin.partials.pagination-arrows', ['paginator' => $ordersByMonthPaginator])
        @endif
    </div>
</div>

{{-- Orders list --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Paid orders</h3>
    <div class="divide-y divide-gray-100">
        @forelse ($orders as $o)
        <div class="p-4 flex flex-wrap justify-between items-center gap-2 hover:bg-gray-50/50 transition">
            <div>
                <p class="font-medium text-gray-900">#{{ $o->order_number }}</p>
                <p class="text-sm text-gray-500">{{ $o->user?->name ?? 'Guest' }} • {{ $o->created_at->format('M d, Y H:i') }}</p>
            </div>
            <p class="font-bold text-primary">{{ number_format($o->total, 0) }} PKR</p>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No paid orders in this period</div>
        @endforelse
    </div>
    @if ($orders->hasPages())
    @include('admin.partials.pagination-arrows', ['paginator' => $orders])
    @endif
</div>

@if(isset($ordersByDay) && $ordersByDay->isNotEmpty())
@php
    $chartLabels = $ordersByDay->map(function($r) { return \Carbon\Carbon::parse($r->date)->format('M d'); })->values()->all();
    $chartData = $ordersByDay->pluck('revenue')->values()->all();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    var ctx = document.getElementById('salesChart');
    if (!ctx) return;
    var labels = @json($chartLabels);
    var data = @json($chartData);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (PKR)',
                data: data,
                backgroundColor: 'rgba(23, 144, 215, 0.2)',
                borderColor: '#1790d7',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return v ? Number(v).toLocaleString() : v; } } }
            }
        }
    });
})();
</script>
@endif
@endsection
