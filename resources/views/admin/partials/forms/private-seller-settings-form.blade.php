@php
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary';
    $select = $input;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6 mb-6">
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
                'help' => 'Private listings stay pending until you approve them.',
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
                <p class="text-xs text-gray-500 mt-1">0 = unlimited</p>
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
        <div>
            <h2 class="text-base font-semibold text-gray-900">Order deductions from private seller earnings</h2>
            <p class="text-xs text-gray-500 mt-1">Applied per product line after discount when a private seller order is fulfilled. Shipping is excluded.</p>
        </div>
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 sm:p-5">
                <p class="text-sm font-semibold text-gray-900 mb-1">Order commission</p>
                <p class="text-xs text-gray-500 mb-4">Platform commission on each private seller sale.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                        <select name="private_seller_commission_type" class="{{ $select }}">
                            <option value="percentage" @selected(old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'percentage')>Percentage (%)</option>
                            <option value="fixed" @selected(old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'fixed')>Fixed amount (PKR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Value</label>
                        <input type="number" step="0.01" min="0" name="private_seller_commission_value" value="{{ old('private_seller_commission_value', $settings['private_seller_commission_value']) }}" class="{{ $input }}">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @include('admin.partials.forms.settings-fee-fields', [
                    'settings' => $settings,
                    'input' => $input,
                    'select' => $select,
                    'typeName' => 'private_seller_marketplace_fee_type',
                    'valueName' => 'private_seller_marketplace_fee_value',
                    'label' => 'Marketplace fee',
                    'hint' => 'Deducted from private seller product earnings.',
                ])
                @include('admin.partials.forms.settings-fee-fields', [
                    'settings' => $settings,
                    'input' => $input,
                    'select' => $select,
                    'typeName' => 'private_seller_online_transaction_fee_type',
                    'valueName' => 'private_seller_online_transaction_fee_value',
                    'label' => 'Online transaction fee',
                    'hint' => 'Deducted on online-paid orders only (not COD).',
                ])
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Order reject penalty</h2>
            <p class="text-xs text-gray-500 mt-1">Charged to private / shop seller wallet when they reject a paid order. Negative wallet balance is allowed. Business shop sellers use this same amount.</p>
        </div>
        <div class="max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1">Penalty amount (PKR)</label>
            <input type="number" name="order_reject_penalty_private_seller" value="{{ old('order_reject_penalty_private_seller', $settings['order_reject_penalty_private_seller'] ?? '1000') }}" min="0" max="1000000" step="0.01" class="{{ $input }} max-w-[10rem]">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
        <h2 class="text-base font-semibold text-gray-900 mb-1">Verification after login</h2>
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
        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Private Seller Settings</button>
    </div>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">KYC verification</h2>
            <p class="text-xs text-gray-500 mt-1">Private sellers submit ID documents before payouts are released.</p>
        </div>
        <a href="{{ route('admin.customer-sellers.index', ['kyc' => 'pending']) }}" class="text-sm text-primary hover:underline whitespace-nowrap">Review sellers →</a>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-2xl font-bold text-gray-900">{{ $pendingKyc }}</span>
        <span class="text-sm text-gray-600">{{ \Illuminate\Support\Str::plural('submission', $pendingKyc) }} awaiting review</span>
    </div>
</div>
