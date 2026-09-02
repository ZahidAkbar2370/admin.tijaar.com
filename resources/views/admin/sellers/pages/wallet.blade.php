@extends('admin.layouts.app')
@section('title', 'Wallet — Seller #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500'; @endphp
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-6 border-b border-gray-100">
        <div><h1 class="text-xl font-bold text-gray-900">Update Wallet</h1><p class="text-sm text-gray-500 mt-1">Credit or debit seller wallet balance.</p></div>
        <div class="text-right"><p class="text-xs uppercase font-semibold text-gray-500">Available balance</p><p class="text-3xl font-bold text-emerald-700">{{ number_format((float) ($wallet->balance ?? 0), 2) }} <span class="text-base text-gray-500">PKR</span></p></div>
    </div>
    <form method="POST" action="{{ route('admin.sellers.wallet-adjust', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @csrf
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Amount (+ credit / − debit)</label><input type="number" step="0.01" name="amount" required class="{{ $inputClass }}" placeholder="500 or -100"></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Note</label><input type="text" name="note" class="{{ $inputClass }}" placeholder="Reason for adjustment"></div>
        <div class="sm:col-span-2"><button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-medium">Update wallet</button></div>
    </form>
</section>
@endsection
