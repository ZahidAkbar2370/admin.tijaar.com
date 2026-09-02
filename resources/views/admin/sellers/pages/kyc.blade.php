@extends('admin.layouts.app')
@section('title', 'KYC & Bank — Seller #' . $user->id)
@section('admin-content')
@php
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500';
    $seller = $user->seller;
    $kycStatus = $seller->kyc_status ?? 'none';
@endphp
@include('admin.sellers.partials.seller-nav')
@if ($seller)
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 mb-6">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Seller status &amp; KYC actions</h1>
    <p class="text-sm text-gray-500 mb-6">Approve the seller account or verify identity documents.</p>
    <div class="flex flex-wrap gap-3">
        @if ($seller->status === 'pending')
            <form method="POST" action="{{ route('admin.sellers.approve', $user) }}">@csrf<button type="submit" class="px-4 py-2.5 bg-emerald-100 text-emerald-700 rounded-xl text-sm font-medium">Approve seller</button></form>
            <form method="POST" action="{{ route('admin.sellers.reject', $user) }}" class="flex flex-wrap items-center gap-2">@csrf<input type="text" name="rejection_reason" placeholder="Rejection reason" required class="text-sm border border-gray-200 rounded-xl px-3 py-2"><button type="submit" class="px-4 py-2.5 bg-red-100 text-red-700 rounded-xl text-sm font-medium">Reject seller</button></form>
        @endif
        @if ($kycStatus !== 'verified')
            <form method="POST" action="{{ route('admin.sellers.verify-kyc', $user) }}">@csrf<button type="submit" class="px-4 py-2.5 bg-blue-100 text-blue-700 rounded-xl text-sm font-medium">Approve KYC</button></form>
        @endif
        @if (in_array($kycStatus, ['verified', 'pending'], true))
            <form method="POST" action="{{ route('admin.sellers.reject-kyc', $user) }}">@csrf<button type="submit" class="px-4 py-2.5 bg-red-100 text-red-700 rounded-xl text-sm font-medium">Reject KYC</button></form>
        @endif
    </div>
</section>
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h2 class="text-lg font-bold text-gray-900 mb-6">Bank, tax &amp; KYC details</h2>
    <form method="POST" action="{{ route('admin.sellers.update', $user) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        <input type="hidden" name="_return" value="kyc">
        <input type="hidden" name="name" value="{{ $user->name }}">
        <input type="hidden" name="email" value="{{ $user->email }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Tax ID</label><input type="text" name="tax_id" value="{{ old('tax_id', $seller->tax_id) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">KYC status</label>
                <select name="kyc_status" class="{{ $inputClass }}">
                    @foreach (['none','pending','verified','rejected'] as $st)<option value="{{ $st }}" @selected($kycStatus === $st)>{{ ucfirst($st) }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">KYC rejection reason</label><input type="text" name="kyc_rejection_reason" value="{{ old('kyc_rejection_reason', $seller->rejection_reason) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Bank name</label><input type="text" name="bank_name" value="{{ old('bank_name', $seller->bank_name) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Account holder</label><input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $seller->bank_account_holder) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Account number</label><input type="text" name="bank_account_number" value="{{ old('bank_account_number', $seller->bank_account_number) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">SWIFT / IFSC</label><input type="text" name="bank_swift_code" value="{{ old('bank_swift_code', $seller->bank_swift_code) }}" class="{{ $inputClass }}"></div>
            @include('admin.sellers.partials.kyc-document-fields', ['seller' => $seller, 'inputClass' => $inputClass])
        </div>
        <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-medium">Save KYC &amp; bank</button>
    </form>
</section>
@else
<section class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center text-gray-500">No seller record found.</section>
@endif
@endsection
