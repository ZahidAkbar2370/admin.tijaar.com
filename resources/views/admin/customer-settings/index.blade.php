@extends('admin.layouts.app')

@section('title', 'Customer Settings')

@section('admin-content')
@php
    $input = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
    $select = $input;
@endphp

<div class="w-full min-w-0">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Customer Settings</h1>
        <p class="text-sm text-gray-500 mt-1">All customer listing, fee, and commission settings in one place.</p>
    </div>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.customer-settings.update') }}" class="space-y-6">
        @csrf

        {{-- Free listing --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Free listing</h2>
                    <p class="text-xs text-gray-500">How many products a customer can list without paying</p>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Free listing limit</label>
                    <input type="number" name="private_listing_free_limit" value="{{ old('private_listing_free_limit', $settings['private_listing_free_limit']) }}" min="0" max="100" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">Products a customer can publish for free (0 = every listing needs the fee and starts as draft). Beyond this, each new listing needs the listing fee.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max products (with plan)</label>
                    <input type="number" name="private_listing_limit" value="{{ old('private_listing_limit', $settings['private_listing_limit']) }}" min="1" max="100" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">Absolute cap per customer. Grant a higher personal limit from the customer detail page.</p>
                </div>
            </div>
        </section>

        {{-- Listing fee after free limit --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Per-item listing fee</h2>
                    <p class="text-xs text-gray-500">Charged after the free listing limit is exceeded</p>
                </div>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount per product (PKR)</label>
                <input type="number" name="private_listing_fee" value="{{ old('private_listing_fee', $settings['private_listing_fee']) }}" min="0" max="100000" step="0.01" class="{{ $input }} max-w-[12rem]">
                <p class="text-xs text-gray-500 mt-1.5">Paid via wallet or JazzCash to activate each listing above the free limit. New paid listings stay in Draft until paid.</p>
            </div>
        </section>

        {{-- Media rules for customer sell-an-item --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Sell an Item — media</h2>
                    <p class="text-xs text-gray-500">Customer acting as seller (not business store seller)</p>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max images per listing</label>
                    <input type="number" name="private_listing_max_images" value="{{ old('private_listing_max_images', $settings['private_listing_max_images'] ?? 6) }}" min="1" max="12" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">Minimum is always 1 photo. Default 6.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Allow video URL</label>
                    <select name="private_listing_video_enabled" class="{{ $select }} max-w-[14rem]">
                        <option value="0" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled'] ?? '0') === '0')>Disabled</option>
                        <option value="1" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled'] ?? '0') === '1')>Enabled</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">When disabled, customers cannot attach a YouTube/Vimeo link on Sell an Item.</p>
                </div>
            </div>
        </section>

        {{-- Listing expiry --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Product / item listing expiry</h2>
                    <p class="text-xs text-gray-500">How long a customer listing stays live</p>
                </div>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry (days)</label>
                <input type="number" name="private_listing_expiry_days" value="{{ old('private_listing_expiry_days', $settings['private_listing_expiry_days']) }}" min="1" max="365" class="{{ $input }} max-w-[10rem]">
                <p class="text-xs text-gray-500 mt-1.5">Customer listings auto-expire after this many days from publish.</p>
            </div>
        </section>

        {{-- Customer commission --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Customer commission</h2>
                    <p class="text-xs text-gray-500">Deducted from the customer’s earnings when they sell a private listing</p>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="private_seller_commission_type" class="{{ $select }} min-w-[12rem]">
                            <option value="percentage" {{ old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                            <option value="fixed" {{ old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (PKR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value</label>
                        <input type="number" step="0.01" min="0" name="private_seller_commission_value" value="{{ old('private_seller_commission_value', $settings['private_seller_commission_value']) }}" class="{{ $input }} max-w-[10rem]">
                        <p class="text-xs text-gray-400 mt-1">e.g. 2 for 2%, or 50 for Rs 50</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Marketplace fee --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Marketplace fee</h2>
                    <p class="text-xs text-gray-500">Added to the buyer’s order total on every sale (platform revenue)</p>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="marketplace_fee_type" class="{{ $select }} min-w-[12rem]">
                            <option value="fixed" {{ old('marketplace_fee_type', $settings['marketplace_fee_type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (PKR)</option>
                            <option value="percentage" {{ old('marketplace_fee_type', $settings['marketplace_fee_type']) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value</label>
                        <input type="number" step="0.01" min="0" name="marketplace_fee_value" value="{{ old('marketplace_fee_value', $settings['marketplace_fee_value']) }}" class="{{ $input }} max-w-[10rem]">
                        <p class="text-xs text-gray-400 mt-1">e.g. 20 (Rs) or 2 (%)</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Online transaction fee --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Online transaction fee</h2>
                    <p class="text-xs text-gray-500">Charged to the buyer only on online payments — not on pure COD</p>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="online_transaction_fee_type" class="{{ $select }} min-w-[12rem]">
                            <option value="fixed" {{ old('online_transaction_fee_type', $settings['online_transaction_fee_type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (PKR)</option>
                            <option value="percentage" {{ old('online_transaction_fee_type', $settings['online_transaction_fee_type']) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value</label>
                        <input type="number" step="0.01" min="0" name="online_transaction_fee_value" value="{{ old('online_transaction_fee_value', $settings['online_transaction_fee_value']) }}" class="{{ $input }} max-w-[10rem]">
                    </div>
                </div>
            </div>
        </section>

        {{-- Payout hold --}}
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900">Customer payout hold</h2>
                    <p class="text-xs text-gray-500">Optional hold before customer (private seller) earnings become available</p>
                </div>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payout hold (days)</label>
                <input type="number" name="private_payout_hold_days" value="{{ old('private_payout_hold_days', $settings['private_payout_hold_days']) }}" min="0" max="90" placeholder="{{ $globalPayoutHoldDays }}" class="{{ $input }} max-w-[10rem]">
                <p class="text-xs text-gray-500 mt-1.5">
                    Leave blank to use the global seller hold
                    (currently {{ $globalPayoutHoldDays }} {{ \Illuminate\Support\Str::plural('day', $globalPayoutHoldDays) }}, under
                    <a href="{{ route('admin.settings.index') }}" class="text-primary hover:underline">General Settings → Seller Payout</a>).
                    A per-user hold on the customer detail page still wins.
                </p>
            </div>
        </section>

        <div class="sticky bottom-4 z-10">
            <div class="bg-white/95 backdrop-blur border border-gray-200 shadow-lg rounded-2xl px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">Save all customer settings at once.</p>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Customer Settings</button>
            </div>
        </div>
    </form>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
            <p class="font-medium mb-1">How an order adds up</p>
            <ul class="list-disc list-inside space-y-1 text-slate-600 text-xs sm:text-sm">
                <li>Buyer pays: price + marketplace fee + online fee (if online) + shipping</li>
                <li>Customer seller receives: price − customer commission</li>
                <li>Tijaar revenue: marketplace fee + online fee + customer commission</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
            <p class="font-medium mb-1">Cash on Delivery</p>
            <p class="text-slate-600 text-xs sm:text-sm">
                COD is currently
                <span class="font-medium {{ $codEnabled ? 'text-emerald-700' : 'text-red-700' }}">{{ $codEnabled ? 'enabled' : 'disabled' }}</span>.
                Change under <a href="{{ route('admin.payment-methods.cod') }}" class="text-primary hover:underline">Payment Method → Cash on Delivery</a>.
            </p>
        </div>
    </div>
</div>
@endsection
