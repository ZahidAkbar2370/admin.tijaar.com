@php
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
    $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/');
    $productPackages = ['featured_product', 'hot_sale'];
    $storePackages = ['featured_shop', 'store_banner'];
@endphp

<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6" x-data="{
    paymentStatus: 'paid',
    paymentMethod: 'admin',
    packageType: '',
    onPackageChange(el) {
        this.packageType = el.options[el.selectedIndex]?.dataset?.type || '';
    }
}">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h2 class="text-base font-bold text-gray-900">Wallet & promotions</h2>
            <p class="text-xs text-gray-500">Adjust balance, assign packages (paid or pending payment link)</p>
        </div>
    </div>

    <div class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl border border-gray-100 p-5 bg-gray-50/50">
            <p class="text-sm font-semibold text-gray-800 mb-1">Wallet balance</p>
            <p class="text-2xl font-bold text-gray-900 mb-4">{{ number_format((float) ($wallet->balance ?? 0), 2) }} <span class="text-sm font-medium text-gray-500">PKR</span></p>
            <form method="POST" action="{{ route('admin.users.wallet-adjust', $user) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Amount (+ credit / − debit)</label>
                    <input type="number" step="0.01" name="amount" required class="{{ $inputClass }}" placeholder="e.g. 500 or -100">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Note</label>
                    <input type="text" name="note" class="{{ $inputClass }}" placeholder="Reason for adjustment">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-dark">Update wallet</button>
            </form>
        </div>

        <div class="rounded-xl border border-gray-100 p-5">
            <p class="text-sm font-semibold text-gray-800 mb-4">Assign promotion package</p>
            <form method="POST" action="{{ route('admin.users.assign-promotion', $user) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Package</label>
                    <select name="package_id" required class="{{ $inputClass }}" @change="onPackageChange($event.target)">
                        <option value="">Select package</option>
                        @foreach ($promotionPackages ?? [] as $pkg)
                            <option value="{{ $pkg->id }}" data-type="{{ $pkg->type }}">{{ $pkg->name }} — {{ number_format($pkg->price, 0) }} PKR ({{ $pkg->duration_days }}d)</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="['featured_product','hot_sale'].includes(packageType)" x-cloak>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Product</label>
                    <select name="product_id" class="{{ $inputClass }}">
                        <option value="">Select product</option>
                        @foreach ($userProducts ?? [] as $product)
                            <option value="{{ $product->id }}">#{{ $product->id }} — {{ \Illuminate\Support\Str::limit($product->name, 40) }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="['featured_shop','store_banner'].includes(packageType)" x-cloak>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Store</label>
                    <select name="store_id" class="{{ $inputClass }}">
                        <option value="">Select store</option>
                        @foreach ($userStores ?? [] as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Payment status</label>
                        <select name="payment_status" x-model="paymentStatus" class="{{ $inputClass }}">
                            <option value="paid">Paid (activate now)</option>
                            <option value="pending">Pending (send pay link)</option>
                        </select>
                    </div>
                    <div x-show="paymentStatus === 'paid'" x-cloak>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Paid via</label>
                        <select name="payment_method" x-model="paymentMethod" class="{{ $inputClass }}">
                            <option value="admin">Admin / system</option>
                            <option value="wallet">User wallet</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Admin note</label>
                    <textarea name="admin_note" rows="2" class="{{ $inputClass }}" placeholder="Internal note"></textarea>
                </div>

                <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-medium hover:bg-amber-600">Assign promotion</button>
            </form>
        </div>
    </div>

    @if (($userPromotions ?? collect())->isNotEmpty())
    <div class="px-6 pb-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Promotion history</h3>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Package</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Paid by</th>
                        <th class="px-4 py-3">Period</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($userPromotions as $promo)
                    <tr>
                        <td class="px-4 py-3">{{ $promo->package?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ ucfirst($promo->status) }}</td>
                        <td class="px-4 py-3">
                            {{ ucfirst($promo->payment_status ?? 'paid') }}
                            @if ($promo->payment_status === 'pending' && $promo->payment_link_token)
                                <br><span class="text-xs text-primary break-all">{{ $frontendUrl }}/customer/promotion-packages?pay={{ $promo->payment_link_token }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $promo->paid_by ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if ($promo->starts_at)
                                {{ $promo->starts_at->format('M d') }} – {{ $promo->ends_at?->format('M d, Y') ?? '—' }}
                            @else
                                Pending activation
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>

@if (($userProducts ?? collect())->isNotEmpty())
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-gray-900">Products</h2>
        <a href="{{ route('admin.products.index', ['seller_id' => $user->id]) }}" class="text-sm text-primary hover:underline">View in catalog →</a>
    </div>
    <div class="p-6 text-sm text-gray-600">{{ ($userProducts ?? collect())->count() }} product(s) linked to this account.</div>
</section>
@endif
