@extends('admin.layouts.app')

@section('title', 'Transaction #' . $payment->id)

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Transactions
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <section class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-1">Transaction #{{ $payment->id }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ ucfirst($payment->gateway ?? 'unknown') }} · {{ ucfirst($payment->status) }}</p>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-gray-500">Amount</dt><dd class="font-semibold text-gray-900">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency ?? 'PKR' }}</dd></div>
            <div><dt class="text-gray-500">Gateway reference</dt><dd class="font-mono text-gray-900 break-all">{{ $payment->gateway_reference ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Paid at</dt><dd class="text-gray-900">{{ $payment->paid_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Created</dt><dd class="text-gray-900">{{ $payment->created_at?->format('M d, Y g:i A') }}</dd></div>
        </dl>

        @if ($payment->order)
        <div class="mt-6 pt-6 border-t border-gray-100">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Linked order</h2>
            <a href="{{ route('admin.orders.show', $payment->order) }}" class="text-primary hover:underline font-medium">{{ $payment->order->order_number }}</a>
            <p class="text-sm text-gray-500 mt-1">{{ $payment->order->user?->name }} · {{ number_format((float) $payment->order->total, 2) }} PKR</p>
        </div>
        @endif
    </section>

    <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Gateway response</h2>
        @if (!empty($payment->gateway_response))
            <pre class="text-xs bg-gray-50 border border-gray-100 rounded-xl p-4 overflow-x-auto max-h-96">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <p class="text-sm text-gray-500">No gateway response stored.</p>
        @endif
    </section>
</div>

@if ($payment->logs->isNotEmpty())
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Payment logs</h2></div>
    <div class="divide-y divide-gray-100">
        @foreach ($payment->logs as $log)
        <div class="px-6 py-4 text-sm">
            <p class="font-medium text-gray-800">{{ $log->event ?? 'Log' }} · {{ $log->created_at?->format('M d, Y g:i A') }}</p>
            @if ($log->payload)
                <pre class="mt-2 text-xs bg-gray-50 rounded-lg p-3 overflow-x-auto">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
        @endforeach
    </div>
</section>
@endif
@endsection
