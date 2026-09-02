@extends('admin.layouts.app')

@section('title', 'Seller Settings')

@section('admin-content')
@php
    $input = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
    $select = $input;
@endphp

<div class="w-full min-w-0">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Seller Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Business seller product approval, payout hold, and commission — in one place.</p>
    </div>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.seller-settings.update') }}" class="space-y-6">
        @csrf

        {{-- Product approval --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Product approval</h2>
                    <p class="text-xs text-gray-500">Control whether new seller products need admin review</p>
                </div>
            </div>
            <div class="px-6 py-2">
                @include('admin.partials.setting-toggle', [
                    'name' => 'product_approval_required',
                    'value' => $settings['product_approval_required'],
                    'label' => 'Require approval for seller products',
                    'help' => 'When enabled, products from store sellers stay pending until you approve them. When off, Publish goes live immediately.',
                ])
            </div>
        </section>

        {{-- Payout hold --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Seller payout hold</h2>
                    <p class="text-xs text-gray-500">Days after delivery before business seller earnings become available</p>
                </div>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payout hold (days)</label>
                <input type="number" name="payout_hold_days" value="{{ old('payout_hold_days', $settings['payout_hold_days']) }}" min="0" max="90" class="{{ $input }} max-w-[10rem]">
                <p class="text-xs text-gray-500 mt-1.5">
                    0 = release immediately. Customer (private listing) hold is separate under
                    <a href="{{ route('admin.customer-settings.index') }}" class="text-primary hover:underline">Customer Settings</a>.
                </p>
            </div>
        </section>

        {{-- Seller commission --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Seller commission</h2>
                    <p class="text-xs text-gray-500">Deducted from business seller (shop) earnings on every sale</p>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="seller_commission_type" class="{{ $select }} min-w-[12rem]">
                            <option value="percentage" {{ old('seller_commission_type', $settings['seller_commission_type']) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                            <option value="fixed" {{ old('seller_commission_type', $settings['seller_commission_type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (PKR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value</label>
                        <input type="number" step="0.01" min="0" name="seller_commission_value" value="{{ old('seller_commission_value', $settings['seller_commission_value']) }}" class="{{ $input }} max-w-[10rem]">
                        <p class="text-xs text-gray-400 mt-1">e.g. 2 for 2%, or 50 for Rs 50</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">
                    Saved as the global commission rule. Per-seller / per-category overrides live under
                    <a href="{{ route('admin.commissions.index') }}" class="text-primary hover:underline">Advanced Commission Rules</a>.
                    Customer commission is under
                    <a href="{{ route('admin.customer-settings.index') }}" class="text-primary hover:underline">Customer Settings</a>.
                </p>
            </div>
        </section>

        <div class="sticky bottom-4 z-10">
            <div class="bg-white/95 backdrop-blur border border-gray-200 shadow-lg rounded-2xl px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">Save all seller settings at once.</p>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Seller Settings</button>
            </div>
        </div>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
        <p class="font-medium mb-1">How seller earnings work</p>
        <ul class="list-disc list-inside space-y-1 text-slate-600 text-xs sm:text-sm">
            <li>Buyer pays: price + marketplace fee + online fee (if online) + shipping</li>
            <li>Business seller receives: price − seller commission (after payout hold)</li>
            <li>Tijaar revenue: marketplace fee + online fee + seller commission</li>
        </ul>
    </div>
</div>
@endsection
