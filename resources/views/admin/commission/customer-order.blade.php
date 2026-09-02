@extends('admin.layouts.app')

@section('title', 'Customer Order Commission')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Customer Order Commission</h1>
    <p class="text-sm text-gray-500 mb-6">Fees charged to the customer on top of the order price. Both are Tijaar revenue and are configured independently of seller commission.</p>

    @include('admin.commission.partials.nav')
    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.commission-settings.customer-order.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-8">
        @csrf

        <section class="pb-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900 mb-1">Marketplace fee</h2>
            <p class="text-xs text-gray-500 mb-4">Added to every order total, regardless of payment method.</p>
            <div class="flex flex-wrap gap-4 items-start">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="marketplace_fee_type" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="fixed" {{ $settings['marketplace_fee_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount (PKR)</option>
                        <option value="percentage" {{ $settings['marketplace_fee_type'] === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                    <input type="number" step="0.01" min="0" name="marketplace_fee_value" value="{{ old('marketplace_fee_value', $settings['marketplace_fee_value']) }}"
                           class="w-40 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <p class="text-xs text-gray-400 mt-1">e.g. 20 (Rs) or 2 (%)</p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-semibold text-gray-900 mb-1">Online transaction fee</h2>
            <p class="text-xs text-gray-500 mb-4">Added only when the customer pays online. Pure Cash on Delivery orders are not charged this fee.</p>
            <div class="flex flex-wrap gap-4 items-start">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="online_transaction_fee_type" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="fixed" {{ $settings['online_transaction_fee_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount (PKR)</option>
                        <option value="percentage" {{ $settings['online_transaction_fee_type'] === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                    <input type="number" step="0.01" min="0" name="online_transaction_fee_value" value="{{ old('online_transaction_fee_value', $settings['online_transaction_fee_value']) }}"
                           class="w-40 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>
        </section>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Customer Fees</button>
        </div>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        <p class="font-medium mb-2">How an order adds up</p>
        <ul class="list-disc list-inside space-y-1 text-slate-600">
            <li>Customer pays: order price + marketplace fee + online transaction fee + shipping</li>
            <li>Seller receives: order price − seller commission (set under Seller Commission / Private Seller Commission)</li>
            <li>Tijaar revenue: marketplace fee + online transaction fee + seller commission</li>
        </ul>
    </div>
</div>
@endsection
