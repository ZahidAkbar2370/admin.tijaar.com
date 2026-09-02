@extends('admin.layouts.app')

@section('title', 'Private Seller Setting')

@section('admin-content')
@php
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
    $select = $input;
@endphp
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Private Seller Setting</h1>
    <p class="text-sm text-gray-500 mb-6">
        Control the private seller program, media limits, order fees, and post-login verification requirements.
    </p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.private-seller-settings.update') }}" class="space-y-6 mb-6">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Program</h2>
            <div class="divide-y divide-gray-100">
                @include('admin.partials.setting-toggle', [
                    'name' => 'private_sellers_enabled',
                    'value' => $settings['private_sellers_enabled'],
                    'label' => 'Enable private sellers',
                    'help' => 'Allow customers to list items for sale without creating a shop.',
                ])
                @include('admin.partials.setting-toggle', [
                    'name' => 'private_listing_approval',
                    'value' => $settings['private_listing_approval'],
                    'label' => 'Admin approval required',
                    'help' => 'Private listings stay pending until you approve them. When off, they go live immediately.',
                ])
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-900">Media</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max images per listing</label>
                    <input type="number" name="private_listing_max_images" value="{{ old('private_listing_max_images', $settings['private_listing_max_images']) }}" min="1" max="12" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max image update count</label>
                    <input type="number" name="private_listing_max_image_updates" value="{{ old('private_listing_max_image_updates', $settings['private_listing_max_image_updates']) }}" min="0" max="100" class="{{ $input }}">
                    <p class="text-xs text-gray-500 mt-1">How many times a seller can change listing images. <strong>0 = unlimited</strong>.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Allow video</label>
                    <select name="private_listing_video_enabled" class="{{ $select }}">
                        <option value="0" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled']) === '0')>Disabled</option>
                        <option value="1" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled']) === '1')>Enabled</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-900">Order fees</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <p class="text-sm font-medium text-gray-800">Seller commission (on order)</p>
                    <div class="flex flex-wrap gap-3">
                        <select name="private_seller_commission_type" class="{{ $select }} min-w-[10rem]">
                            <option value="percentage" @selected(old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'percentage')>Percentage</option>
                            <option value="fixed" @selected(old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'fixed')>Fixed (PKR)</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="private_seller_commission_value" value="{{ old('private_seller_commission_value', $settings['private_seller_commission_value']) }}" class="{{ $input }} max-w-[8rem]">
                    </div>
                </div>
                <div class="space-y-3">
                    <p class="text-sm font-medium text-gray-800">Marketplace fee (on order)</p>
                    <div class="flex flex-wrap gap-3">
                        <select name="marketplace_fee_type" class="{{ $select }} min-w-[10rem]">
                            <option value="fixed" @selected(old('marketplace_fee_type', $settings['marketplace_fee_type']) === 'fixed')>Fixed (PKR)</option>
                            <option value="percentage" @selected(old('marketplace_fee_type', $settings['marketplace_fee_type']) === 'percentage')>Percentage</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="marketplace_fee_value" value="{{ old('marketplace_fee_value', $settings['marketplace_fee_value']) }}" class="{{ $input }} max-w-[8rem]">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Verification after login</h2>
            <p class="text-xs text-gray-500 mb-3">
                When enabled for an approved private seller, they must complete verification before accessing selling menus or the dashboard.
                When disabled, they can sell immediately after login.
            </p>
            <div class="divide-y divide-gray-100">
                @include('admin.partials.setting-toggle', [
                    'name' => 'private_seller_must_verify_email',
                    'value' => $settings['private_seller_must_verify_email'],
                    'label' => 'Require email verification',
                    'help' => 'Private seller must verify email before selling tools unlock.',
                ])
                @include('admin.partials.setting-toggle', [
                    'name' => 'private_seller_must_verify_phone',
                    'value' => $settings['private_seller_must_verify_phone'],
                    'label' => 'Require phone verification',
                    'help' => 'Private seller must verify mobile before selling tools unlock.',
                ])
                @include('admin.partials.setting-toggle', [
                    'name' => 'private_seller_must_verify_whatsapp',
                    'value' => $settings['private_seller_must_verify_whatsapp'],
                    'label' => 'Require WhatsApp verification',
                    'help' => 'Private seller must verify WhatsApp before selling tools unlock.',
                ])
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Settings</button>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">KYC verification</h2>
                <p class="text-xs text-gray-500 mt-1">Private sellers submit ID documents before payouts are released.</p>
            </div>
            <a href="{{ route('admin.private-sellers.index') }}" class="text-sm text-primary hover:underline whitespace-nowrap">Review →</a>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-2xl font-bold text-gray-900">{{ $pendingKyc }}</span>
            <span class="text-sm text-gray-600">{{ \Illuminate\Support\Str::plural('submission', $pendingKyc) }} awaiting review</span>
        </div>
    </div>
</div>
@endsection
