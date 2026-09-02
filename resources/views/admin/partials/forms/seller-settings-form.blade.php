@php
    $input = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
    $select = $input;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Card header --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-amber-50/80 via-white to-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Customer as Seller</h2>
                    <p class="text-sm text-gray-500 mt-1 max-w-3xl">Rules when customers list and sell items, plus fees deducted from their product earnings after a sale (never from shipping).</p>
                </div>
            </div>
        </div>

        {{-- 1. Listing limits & fees --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">1</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Listing limits &amp; fees</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Free listing limit</label>
                    <input type="number" name="private_listing_free_limit" value="{{ old('private_listing_free_limit', $settings['private_listing_free_limit']) }}" min="0" max="100" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">Products listed free. 0 = all need paid activation.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max products (with plan)</label>
                    <input type="number" name="private_listing_limit" value="{{ old('private_listing_limit', $settings['private_listing_limit']) }}" min="1" max="100" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">Absolute cap per customer.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Per-item listing fee (PKR)</label>
                    <input type="number" name="private_listing_fee" value="{{ old('private_listing_fee', $settings['private_listing_fee']) }}" min="0" max="100000" step="0.01" class="{{ $input }} max-w-[10rem]">
                    <p class="text-xs text-gray-500 mt-1.5">After free limit is exceeded.</p>
                </div>
            </div>
        </div>

        {{-- 2. Listing content rules --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-violet-100 text-violet-700 text-xs font-bold">2</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Listing content &amp; expiry</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max images per listing</label>
                    <input type="number" name="private_listing_max_images" value="{{ old('private_listing_max_images', $settings['private_listing_max_images'] ?? 6) }}" min="1" max="12" class="{{ $input }} max-w-[10rem]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Allow video URL</label>
                    <select name="private_listing_video_enabled" class="{{ $select }} max-w-[14rem]">
                        <option value="0" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled'] ?? '0') === '0')>Disabled</option>
                        <option value="1" @selected((string) old('private_listing_video_enabled', $settings['private_listing_video_enabled'] ?? '0') === '1')>Enabled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Listing expiry (days)</label>
                    <input type="number" name="private_listing_expiry_days" value="{{ old('private_listing_expiry_days', $settings['private_listing_expiry_days']) }}" min="1" max="365" class="{{ $input }} max-w-[10rem]">
                </div>
            </div>
        </div>

        {{-- 3. Order deductions --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-xs font-bold">3</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Order deductions from seller earnings</h3>
            </div>
            <p class="text-xs text-gray-500 mb-5 ml-8">Applied per product line after discount. Shipping is excluded.</p>
            <div class="space-y-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 sm:p-5">
                    <p class="text-sm font-semibold text-gray-900 mb-1">Customer commission</p>
                    <p class="text-xs text-gray-500 mb-4">Platform commission on each sale.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                            <select name="private_seller_commission_type" class="{{ $select }}">
                                <option value="percentage" {{ old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="fixed" {{ old('private_seller_commission_type', $settings['private_seller_commission_type']) === 'fixed' ? 'selected' : '' }}>Fixed amount (PKR)</option>
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
                        'typeName' => 'private_seller_marketplace_fee_type',
                        'valueName' => 'private_seller_marketplace_fee_value',
                        'label' => 'Marketplace fee',
                        'hint' => 'Deducted from seller product earnings.',
                    ])
                    @include('admin.partials.forms.settings-fee-fields', [
                        'settings' => $settings,
                        'typeName' => 'private_seller_online_transaction_fee_type',
                        'valueName' => 'private_seller_online_transaction_fee_value',
                        'label' => 'Online transaction fee',
                        'hint' => 'Deducted on online-paid orders only (not COD).',
                    ])
                </div>
            </div>
        </div>

        {{-- 4. Order reject penalty --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-700 text-xs font-bold">4</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Order reject penalty</h3>
            </div>
            <p class="text-xs text-gray-500 mb-5 ml-8">Charged to the customer-as-seller wallet when they reject a paid order. Negative wallet balance is allowed.</p>
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-2">Penalty amount (PKR)</label>
                <input type="number" name="order_reject_penalty_customer_seller" value="{{ old('order_reject_penalty_customer_seller', $settings['order_reject_penalty_customer_seller'] ?? '500') }}" min="0" max="1000000" step="0.01" class="{{ $input }} max-w-[10rem]">
            </div>
        </div>

        {{-- 5. Business shop sellers --}}
        <div class="px-6 sm:px-8 py-6 border-b border-gray-100">
            <div class="flex items-center gap-2 mb-5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-700 text-xs font-bold">5</span>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Business shop sellers</h3>
            </div>
            <div class="space-y-5">
                <div class="rounded-xl border border-gray-100 divide-y divide-gray-100">
                    @include('admin.partials.setting-toggle', [
                        'name' => 'product_approval_required',
                        'value' => $settings['product_approval_required'],
                        'label' => 'Require approval for seller products',
                        'help' => 'Store products stay pending until admin approves them.',
                    ])
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Business payout hold (days)</label>
                        <input type="number" name="payout_hold_days" value="{{ old('payout_hold_days', $settings['payout_hold_days']) }}" min="0" max="90" class="{{ $input }} max-w-[10rem]">
                        <p class="text-xs text-gray-500 mt-1.5">Days after delivery before shop earnings release.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shop seller commission</label>
                        <div class="grid grid-cols-2 gap-3 max-w-md">
                            <select name="seller_commission_type" class="{{ $select }}">
                                <option value="percentage" {{ old('seller_commission_type', $settings['seller_commission_type']) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed" {{ old('seller_commission_type', $settings['seller_commission_type']) === 'fixed' ? 'selected' : '' }}>Fixed (PKR)</option>
                            </select>
                            <input type="number" step="0.01" min="0" name="seller_commission_value" value="{{ old('seller_commission_value', $settings['seller_commission_value']) }}" class="{{ $input }}">
                        </div>
                        <p class="text-xs text-gray-500 mt-1.5">Overrides: <a href="{{ route('admin.commissions.index') }}" class="text-primary hover:underline">Advanced Commission Rules</a></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 sm:px-8 py-4 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-gray-500">Buyer checkout fees are under <a href="{{ route('admin.people-settings.index', ['tab' => 'customer']) }}" class="text-primary hover:underline font-medium">Customer as Buyer</a>.</p>
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shrink-0">Save Seller Settings</button>
        </div>
    </div>
</form>

<div class="mt-5 p-4 sm:p-5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700">
    <p class="font-semibold text-gray-900 mb-2">When a customer sells an item</p>
    <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-slate-600 text-xs sm:text-sm">
        <li class="flex gap-2"><span class="text-amber-500">•</span><span>Buyer pays at checkout (see Customer as Buyer tab)</span></li>
        <li class="flex gap-2"><span class="text-amber-500">•</span><span>Seller receives: product price − commission − fees</span></li>
        <li class="flex gap-2"><span class="text-amber-500">•</span><span>All deductions use product line total — not shipping</span></li>
        <li class="flex gap-2"><span class="text-amber-500">•</span><span>Listing fees charged separately when publishing above free limit</span></li>
    </ul>
</div>
