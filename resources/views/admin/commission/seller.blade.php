@extends('admin.layouts.app')

@section('title', 'Seller Commission')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Seller Commission</h1>
    <p class="text-sm text-gray-500 mb-6">Deducted from business seller (shop) earnings on every sale. Saved as the <strong>global</strong> commission rule; private sellers use their own rate.</p>

    @include('admin.commission.partials.nav')
    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.commission-settings.seller.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="flex flex-wrap gap-4 items-start">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="seller_commission_type" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="percentage" {{ $settings['seller_commission_type'] === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ $settings['seller_commission_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount (PKR)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                <input type="number" step="0.01" min="0" name="seller_commission_value" value="{{ old('seller_commission_value', $settings['seller_commission_value']) }}"
                       class="w-40 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <p class="text-xs text-gray-400 mt-1">e.g. 2 for 2%</p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Seller Commission</button>
            <a href="{{ route('admin.commissions.index') }}" class="ml-3 text-sm text-gray-500 hover:text-primary">Advanced commission rules →</a>
        </div>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        Seller-specific and category-specific rules override this global rate. Customer commission is under <a href="{{ route('admin.people-settings.index', ['tab' => 'customer']) }}" class="text-primary hover:underline">Customer as Buyer</a>; marketplace &amp; online fees under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline">Customer as Seller</a>.
    </div>
</div>
@endsection
