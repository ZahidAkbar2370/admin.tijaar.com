@extends('admin.layouts.app')

@section('title', 'Refund Report')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Refund Report</h1>
    <p class="text-sm text-gray-500 mt-1">Refund requests and amounts by status</p>
</div>

@include('admin.analytics._date_filter', ['route' => route('admin.analytics.refund'), 'period' => $period ?? 30, 'dateFrom' => $dateFrom ?? null, 'dateTo' => $dateTo ?? null, 'from' => $from, 'to' => $to])

<form method="GET" action="{{ route('admin.analytics.refund') }}" class="mb-4 flex gap-2 items-center flex-wrap">
    <input type="hidden" name="period" value="{{ $period ?? 30 }}">
    <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
    <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
    <select name="status" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
    </select>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Total refunded</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['total_amount'] ?? 0, 0) }} PKR</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Total count</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['count'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Pending</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['pending_count'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500 font-medium">Completed</p>
        <p class="text-2xl font-bold text-primary mt-1">{{ $summary['completed_count'] ?? 0 }}</p>
    </div>
</div>

@if(isset($byDayPaginator) && $byDayPaginator->total() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Refunds by day</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80"><tr><th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Count</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Amount (PKR)</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($byDayPaginator as $r)
                <tr><td class="px-4 py-2">{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</td><td class="px-4 py-2 text-right">{{ $r->count }}</td><td class="px-4 py-2 text-right font-medium text-primary">{{ number_format($r->amount ?? 0, 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination-arrows', ['paginator' => $byDayPaginator])
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <h3 class="font-semibold text-gray-900 px-5 pt-5 pb-2">Refund list</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($refunds as $r)
                <tr class="table-row-hover">
                    <td class="px-4 py-3 font-mono">{{ $r->id }}</td>
                    <td class="px-4 py-3">{{ $r->order?->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-primary">{{ number_format($r->amount, 0) }} PKR</td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-lg text-xs font-medium {{ $r->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $r->status }}</span></td>
                    <td class="px-4 py-3 text-gray-500">{{ $r->created_at?->format('M d, Y H:i') }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.refunds.show', $r) }}" class="text-primary hover:underline font-medium">View</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No refunds in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($refunds->hasPages())
    @include('admin.partials.pagination-arrows', ['paginator' => $refunds])
    @endif
</div>
@endsection
