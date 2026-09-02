@extends('admin.layouts.app')
@section('title', 'Private Seller #' . $user->id)
@section('admin-content')
@php
    $seller = $seller ?? $user->seller;
    $kycStatus = $seller->kyc_status ?? 'none';
    $accountStatus = $seller->status ?? 'none';
    $sourceLabel = \App\Services\Admin\SellerAdminService::sourceLabel($user);
@endphp
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-50 via-orange-50/40 to-white px-6 py-8 sm:px-10">
        <div class="flex flex-col sm:flex-row sm:items-start gap-6 mb-8">
            <div class="w-20 h-20 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-3xl font-bold shrink-0">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-1">Private Seller (Business)</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="text-gray-500 mt-1">{{ $user->email }} · Registered via {{ $sourceLabel }}</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Account: {{ ucfirst($accountStatus) }}</span>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-800">KYC: {{ ucfirst($kycStatus) }}</span>
                    @if ($user->email_verified_at)<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">Email verified</span>@endif
                    @if ($user->phone_verified_at)<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">Phone verified</span>@endif
                    @if ($user->whatsapp_verified_at)<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">WhatsApp verified</span>@endif
                    @if ($user->is_banned)<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700">Banned</span>
                    @elseif ($user->is_suspended)<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-700">Suspended</span>
                    @else<span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">Active</span>@endif
                </div>
            </div>
            @if ($store)
            <div class="sm:text-right shrink-0">
                <p class="text-xs text-gray-500 uppercase font-semibold">Store</p>
                <p class="text-lg font-bold text-gray-900">{{ $store->name }}</p>
            </div>
            @endif
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Products</p><p class="text-xl font-bold text-gray-900 mt-1">{{ $productsCount ?? 0 }}</p></div>
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Total Earnings</p><p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalEarningsSum, 0) }} <span class="text-xs text-gray-500">PKR</span></p></div>
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Available Earnings</p><p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($availableEarnings, 0) }} <span class="text-xs text-gray-500">PKR</span></p></div>
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Wallet</p><p class="text-xl font-bold text-emerald-700 mt-1">{{ number_format((float) ($wallet->balance ?? 0), 2) }}</p></div>
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Joined</p><p class="text-sm font-bold text-gray-900 mt-1">{{ $user->created_at?->format('M d, Y') }}</p></div>
            <div class="rounded-xl bg-white border border-gray-100 px-4 py-4"><p class="text-[10px] uppercase font-semibold text-gray-500">Last Login</p><p class="text-sm font-bold text-gray-900 mt-1">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</p></div>
        </div>
    </div>
</section>
@endsection
