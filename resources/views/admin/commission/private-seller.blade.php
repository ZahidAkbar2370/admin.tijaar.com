@extends('admin.layouts.app')

@section('title', 'Private Seller Commission')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Private Seller Commission</h1>
    <p class="text-sm text-gray-500 mb-6">Deducted from the sale amount of customers selling their own items (private listings). Saved as a <strong>seller type: private</strong> rule, which outranks the global seller commission.</p>

    @include('admin.commission.partials.nav')
    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.commission-settings.private-seller.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="flex flex-wrap gap-4 items-start">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="private_seller_commission_type" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="percentage" {{ $settings['private_seller_commission_type'] === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ $settings['private_seller_commission_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount (PKR)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                <input type="number" step="0.01" min="0" name="private_seller_commission_value" value="{{ old('private_seller_commission_value', $settings['private_seller_commission_value']) }}"
                       class="w-40 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="text-xs text-gray-400 mt-1">e.g. 2 for 2%</p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Private Seller Commission</button>
            <a href="{{ route('admin.private-seller-settings.index') }}" class="ml-3 text-sm text-gray-500 hover:text-primary">Private seller settings →</a>
        </div>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        Per-seller and per-category rules still take priority over this rate. Manage them under <a href="{{ route('admin.commissions.index') }}" class="text-primary hover:underline">Advanced Rules</a>.
    </div>
</div>
@endsection
