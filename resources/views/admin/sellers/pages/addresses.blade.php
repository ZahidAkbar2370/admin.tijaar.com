@extends('admin.layouts.app')
@section('title', 'Addresses — Seller #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500'; @endphp
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8 space-y-6">
    <div><h1 class="text-xl font-bold text-gray-900">Addresses</h1><p class="text-sm text-gray-500 mt-1">Manage saved delivery addresses for this seller account.</p></div>
    @forelse ($user->addresses as $addr)
    <form method="POST" action="{{ route('admin.sellers.addresses.update', [$user, $addr]) }}" class="rounded-xl border border-gray-100 bg-gray-50/60 p-5 space-y-3">
        @csrf @method('PUT')
        <p class="font-semibold text-sm">{{ ucfirst($addr->type ?? 'shipping') }} @if($addr->is_default)<span class="text-emerald-600 text-xs">(Default)</span>@endif</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div><label class="block text-xs text-gray-500 mb-1">First name</label><input type="text" name="first_name" value="{{ $addr->first_name }}" class="{{ $inputClass }} bg-white"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Last name</label><input type="text" name="last_name" value="{{ $addr->last_name }}" class="{{ $inputClass }} bg-white"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input type="text" name="phone" value="{{ $addr->phone }}" class="{{ $inputClass }} bg-white"></div>
            <div class="sm:col-span-2 lg:col-span-2"><label class="block text-xs text-gray-500 mb-1">Address line 1</label><input type="text" name="address_line_1" value="{{ $addr->address_line_1 }}" required class="{{ $inputClass }} bg-white"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs text-gray-500 mb-1">Address line 2</label><input type="text" name="address_line_2" value="{{ $addr->address_line_2 }}" class="{{ $inputClass }} bg-white"></div>
            @include('admin.partials.location-select-fields', [
                'inputClass' => $inputClass . ' bg-white',
                'countryValue' => $addr->country,
                'stateValue' => $addr->state,
                'cityValue' => $addr->city,
            ])
            <div><label class="block text-xs text-gray-500 mb-1">ZIP</label><input type="text" name="zip_code" value="{{ $addr->zip_code }}" class="{{ $inputClass }} bg-white"></div>
        </div>
        <button type="submit" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium">Save address</button>
    </form>
    @empty
    <p class="text-sm text-gray-500 py-4">No addresses yet.</p>
    @endforelse
    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-6">
        <h2 class="text-sm font-bold mb-4">Add new address</h2>
        <form method="POST" action="{{ route('admin.sellers.addresses.store', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @csrf
            <div><label class="block text-xs text-gray-500 mb-1">Type</label><select name="type" class="{{ $inputClass }}"><option value="shipping">Shipping</option><option value="billing">Billing</option></select></div>
            <div><label class="block text-xs text-gray-500 mb-1">Label</label><input type="text" name="label" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">First name</label><input type="text" name="first_name" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Last name</label><input type="text" name="last_name" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input type="text" name="phone" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs text-gray-500 mb-1">Address line 1</label><input type="text" name="address_line_1" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs text-gray-500 mb-1">Address line 2</label><input type="text" name="address_line_2" class="{{ $inputClass }}"></div>
            @include('admin.partials.location-select-fields', ['inputClass' => $inputClass])
            <div><label class="block text-xs text-gray-500 mb-1">ZIP</label><input type="text" name="zip_code" class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="inline-flex items-center gap-2 text-sm"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" class="rounded"> Set as default</label></div>
            <div class="sm:col-span-2 lg:col-span-3"><button type="submit" class="px-5 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-medium">Add address</button></div>
        </form>
    </div>
</section>
@endsection
