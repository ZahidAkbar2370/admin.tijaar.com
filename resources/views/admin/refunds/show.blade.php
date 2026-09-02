@extends('admin.layouts.app')

@section('title', 'Refund #' . $refund->id)

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.refunds.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">← Back to Refunds</a>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Refund #{{ $refund->id }}</h2>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-gray-500">Order</dt><dd class="font-medium">{{ $refund->order?->order_number }}</dd></div>
        <div><dt class="text-gray-500">Amount</dt><dd class="font-medium">{{ number_format($refund->amount, 2) }}</dd></div>
        <div><dt class="text-gray-500">Status</dt><dd><span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $refund->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $refund->status }}</span></dd></div>
        <div><dt class="text-gray-500">Reason</dt><dd class="text-gray-700">{{ $refund->reason ?? '—' }}</dd></div>
    </dl>

    @if ($refund->status === 'pending')
    <div class="mt-6 pt-6 border-t border-gray-100">
        <h3 class="font-semibold text-gray-900 mb-3">Process refund</h3>
        <form method="POST" action="{{ route('admin.refunds.process', $refund) }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Refund type</label>
                <select name="refund_type" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required>
                    <option value="gateway">Gateway (Stripe etc.)</option>
                    <option value="wallet">Refund to wallet</option>
                </select>
            </div>
            <div id="gateway-ref-id" class="hidden">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Gateway refund ID</label>
                <input type="text" name="gateway_refund_id" placeholder="Optional" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Process</button>
        </form>
    </div>
    @endif
</div>
@endsection
