@extends('admin.layouts.app')

@section('title', 'Customer #' . $user->id)

@section('admin-content')
@php
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
    $sourceLabel = \App\Support\RegistrationSource::label($user->registration_source);
@endphp

@include('admin.users.partials.customer-nav')

<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-primary/10 via-sky-50 to-white px-6 py-6 sm:px-8">
        <div class="flex items-center gap-4 min-w-0 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-primary text-white flex items-center justify-center shadow-sm shrink-0">
                <span class="text-2xl font-bold">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $user->name }}</h1>
                <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                <div class="flex gap-2 mt-2 flex-wrap">
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">Customer</span>
                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">Registered via {{ $sourceLabel }}</span>
                    @if ($user->email_verified_at)
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">Email verified</span>
                    @endif
                    @if ($user->phone_verified_at)
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">Phone verified</span>
                    @endif
                    @if ($user->whatsapp_verified_at)
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700">WhatsApp verified</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Orders (Buyer)</p>
                <p class="text-lg font-bold text-gray-900">{{ $user->orders_count ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Listings (Seller)</p>
                <p class="text-lg font-bold text-gray-900">{{ $user->private_listings_count ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Total Purchases</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($totalPurchasesSum, 0) }} <span class="text-xs text-gray-500">PKR</span></p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Total Earnings</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($totalEarningsSum, 0) }} <span class="text-xs text-gray-500">PKR</span></p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Wallet Balance</p>
                <p class="text-lg font-bold text-emerald-700">{{ number_format((float) ($wallet->balance ?? 0), 2) }} <span class="text-xs text-gray-500">PKR</span></p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Available Earnings</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($availableEarnings, 0) }} <span class="text-xs text-gray-500">PKR</span></p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Joined</p>
                <p class="text-sm font-bold text-gray-900">{{ $user->created_at?->format('M d, Y') ?? '—' }}</p>
            </div>
            <div class="rounded-xl bg-white/90 border border-gray-100 px-4 py-3">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Last Login</p>
                <p class="text-sm font-bold text-gray-900">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
