@extends('admin.layouts.app')
@section('title', 'Promotions — Seller #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm'; $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/'); @endphp
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8" x-data="{ paymentStatus: 'paid', paymentMethod: 'admin', packageType: '', onPackageChange(el) { this.packageType = el.options[el.selectedIndex]?.dataset?.type || ''; } }">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Purchase Promotion</h1>
    <form method="POST" action="{{ route('admin.sellers.assign-promotion', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
        @csrf
        <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Package</label><select name="package_id" required class="{{ $inputClass }}" @change="onPackageChange($event.target)"><option value="">Select package</option>@foreach ($promotionPackages ?? [] as $pkg)<option value="{{ $pkg->id }}" data-type="{{ $pkg->type }}">{{ $pkg->name }} — {{ number_format($pkg->price, 0) }} PKR</option>@endforeach</select></div>
        <div x-show="['featured_product','hot_sale'].includes(packageType)" x-cloak><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Product</label><select name="product_id" class="{{ $inputClass }}"><option value="">Select product</option>@foreach ($userProducts ?? [] as $p)<option value="{{ $p->id }}">#{{ $p->id }} — {{ \Illuminate\Support\Str::limit($p->name, 40) }}</option>@endforeach</select></div>
        <div x-show="['featured_shop','store_banner'].includes(packageType)" x-cloak><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Store</label><select name="store_id" class="{{ $inputClass }}"><option value="">Select store</option>@foreach ($userStores ?? [] as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Payment status</label><select name="payment_status" x-model="paymentStatus" class="{{ $inputClass }}"><option value="paid">Paid</option><option value="pending">Pending</option></select></div>
        <div x-show="paymentStatus === 'paid'" x-cloak><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Paid via</label><select name="payment_method" x-model="paymentMethod" class="{{ $inputClass }}"><option value="admin">Admin</option><option value="wallet">Wallet</option></select></div>
        <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Admin note</label><textarea name="admin_note" rows="2" class="{{ $inputClass }}"></textarea></div>
        <div class="sm:col-span-2"><button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-medium">Assign promotion</button></div>
    </form>
    <h2 class="text-sm font-semibold mb-3">Recent purchases</h2>
    @if (($userPromotions ?? collect())->isEmpty())<p class="text-sm text-gray-500">No promotions yet.</p>
    @else
    <div class="overflow-x-auto rounded-xl border border-gray-100"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Package</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Payment</th><th class="px-4 py-3 text-left">Period</th></tr></thead><tbody class="divide-y">@foreach ($userPromotions as $promo)<tr><td class="px-4 py-3">{{ $promo->package?->name }}</td><td class="px-4 py-3">{{ ucfirst($promo->status) }}</td><td class="px-4 py-3">{{ ucfirst($promo->payment_status ?? 'paid') }}</td><td class="px-4 py-3 text-xs">@if($promo->starts_at){{ $promo->starts_at->format('M d') }} – {{ $promo->ends_at?->format('M d, Y') }}@else Pending @endif</td></tr>@endforeach</tbody></table></div>
    @endif
</section>
@endsection
