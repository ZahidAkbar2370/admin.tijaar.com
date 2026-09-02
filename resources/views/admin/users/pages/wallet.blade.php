@extends('admin.layouts.app')
@section('title', 'Wallet — Customer #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary'; @endphp
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-bold text-gray-900">Update Wallet</h1>
        <p class="text-xl font-bold text-emerald-700">{{ number_format((float) ($wallet->balance ?? 0), 2) }} <span class="text-sm text-gray-500">PKR</span></p>
    </div>
    <form method="POST" action="{{ route('admin.users.wallet-adjust', $user) }}" class="space-y-3">
        @csrf
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Amount (+ credit / − debit)</label><input type="number" step="0.01" name="amount" required class="{{ $inputClass }}"></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Note</label><input type="text" name="note" class="{{ $inputClass }}"></div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium">Update wallet</button>
    </form>
</section>
@endsection
