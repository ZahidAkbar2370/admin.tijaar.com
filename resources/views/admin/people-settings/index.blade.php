@extends('admin.layouts.app')

@section('title', 'People Settings')

@section('admin-content')
@php
    $tabs = [
        'customer' => 'Customer as Buyer',
        'seller' => 'Customer as Seller',
        'private_seller' => 'Private Seller',
    ];
    $input = 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
@endphp

<div class="w-full min-w-0">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">People Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Manage customer, seller, and private seller preferences, commissions, and program rules.</p>
    </div>

    @include('admin.partials.settings-flash')

    <div class="flex flex-wrap gap-2 mb-6 p-1 bg-gray-100 rounded-xl w-fit">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.people-settings.index', ['tab' => $key]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $tab === $key ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'customer')
        @include('admin.partials.forms.customer-settings-form', [
            'action' => route('admin.people-settings.customer.update'),
            'settings' => $customerSettings,
            'globalPayoutHoldDays' => $globalPayoutHoldDays,
            'codEnabled' => $codEnabled,
        ])
    @elseif ($tab === 'seller')
        @include('admin.partials.forms.seller-settings-form', [
            'action' => route('admin.people-settings.seller.update'),
            'settings' => $sellerSettings,
        ])
    @else
        @include('admin.partials.forms.private-seller-settings-form', [
            'action' => route('admin.people-settings.private-seller.update'),
            'settings' => $privateSellerSettings,
            'pendingKyc' => $pendingKyc,
        ])
    @endif
</div>
@endsection
