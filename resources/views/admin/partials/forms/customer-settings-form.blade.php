@php
    $input = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
    $select = $input;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Card header --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-sky-50/80 via-white to-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-sky-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Customer as Buyer</h2>
                    <p class="text-sm text-gray-500 mt-1 max-w-2xl">Fees added to checkout when a customer places an order. Calculated on product subtotal after discount — shipping is billed separately.</p>
                </div>
            </div>
        </div>

        {{-- 1. Checkout fees --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-sky-700 text-xs font-bold">1</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Checkout fees</h3>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @include('admin.partials.forms.settings-fee-fields', [
                    'settings' => $settings,
                    'typeName' => 'buyer_marketplace_fee_type',
                    'valueName' => 'buyer_marketplace_fee_value',
                    'label' => 'Marketplace fee',
                    'hint' => 'Added to every order total at checkout.',
                ])
                @include('admin.partials.forms.settings-fee-fields', [
                    'settings' => $settings,
                    'typeName' => 'buyer_online_transaction_fee_type',
                    'valueName' => 'buyer_online_transaction_fee_value',
                    'label' => 'Online transaction fee',
                    'hint' => 'Added at checkout for online payments only (not pure COD).',
                ])
            </div>
        </div>

        {{-- 2. Payout hold --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-700 text-xs font-bold">2</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Earnings release</h3>
            </div>
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer-seller payout hold (days)</label>
                <input type="number" name="private_payout_hold_days" value="{{ old('private_payout_hold_days', $settings['private_payout_hold_days']) }}" min="0" max="90" placeholder="{{ $globalPayoutHoldDays }}" class="{{ $input }} max-w-[10rem]">
                <p class="text-xs text-gray-500 mt-2">Optional hold after delivery before a customer’s seller earnings become available. Leave blank to use the global business hold ({{ $globalPayoutHoldDays }} {{ \Illuminate\Support\Str::plural('day', $globalPayoutHoldDays) }}).</p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 sm:px-8 py-4 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-gray-500">Seller listing rules and seller-side deductions are under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline font-medium">Customer as Seller</a>.</p>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shrink-0">Save Buyer Settings</button>
        </div>
    </div>
</form>

<div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="p-4 sm:p-5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        <p class="font-semibold text-gray-900 mb-2">How checkout adds up</p>
        <ul class="space-y-1.5 text-slate-600 text-xs sm:text-sm">
            <li class="flex gap-2"><span class="text-sky-500">•</span><span>Buyer pays: product price + shipping + marketplace fee + online fee (if online)</span></li>
            <li class="flex gap-2"><span class="text-sky-500">•</span><span>Fees use product subtotal after coupon — never shipping</span></li>
        </ul>
    </div>
    <div class="p-4 sm:p-5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        <p class="font-semibold text-gray-900 mb-2">Cash on Delivery</p>
        <p class="text-slate-600 text-xs sm:text-sm">
            COD is
            <span class="font-semibold {{ $codEnabled ? 'text-emerald-700' : 'text-red-700' }}">{{ $codEnabled ? 'enabled' : 'disabled' }}</span>.
            Configure under <a href="{{ route('admin.payment-methods.cod') }}" class="text-primary hover:underline">Payment Method → Cash on Delivery</a>.
        </p>
    </div>
</div>
