@extends('admin.layouts.app')
@section('title', 'Addresses — Customer #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary'; @endphp
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
    <h1 class="text-lg font-bold text-gray-900">Addresses</h1>
    @forelse ($user->addresses as $addr)
    <form method="POST" action="{{ route('admin.users.addresses.update', [$user, $addr]) }}" class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 space-y-3">
        @csrf @method('PUT')
        <p class="font-semibold text-sm">{{ ucfirst($addr->type ?? 'shipping') }} @if($addr->is_default)<span class="text-emerald-600 text-xs">(Default)</span>@endif</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label class="block text-xs text-gray-500 mb-1">First name</label><input type="text" name="first_name" value="{{ $addr->first_name }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Last name</label><input type="text" name="last_name" value="{{ $addr->last_name }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input type="text" name="phone" value="{{ $addr->phone }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">Address line 1</label><input type="text" name="address_line_1" value="{{ $addr->address_line_1 }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">Address line 2</label><input type="text" name="address_line_2" value="{{ $addr->address_line_2 }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
            @include('admin.partials.location-select-fields', [
                'inputClass' => 'w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white',
                'countryValue' => $addr->country,
                'stateValue' => $addr->state,
                'cityValue' => $addr->city,
            ])
            <div><label class="block text-xs text-gray-500 mb-1">ZIP</label><input type="text" name="zip_code" value="{{ $addr->zip_code }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white"></div>
        </div>
        <button type="submit" class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-medium">Save address</button>
    </form>
    @empty
    <p class="text-sm text-gray-500">No addresses yet.</p>
    @endforelse
    <div class="rounded-xl border border-primary/20 bg-primary/5 p-5">
        <h2 class="text-sm font-bold mb-4">Add new address</h2>
        <form method="POST" action="{{ route('admin.users.addresses.store', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @csrf
            <div><label class="block text-xs text-gray-500 mb-1">Type</label><select name="type" class="{{ $inputClass }}"><option value="shipping">Shipping</option><option value="billing">Billing</option></select></div>
            <div><label class="block text-xs text-gray-500 mb-1">Label</label><input type="text" name="label" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">First name</label><input type="text" name="first_name" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Last name</label><input type="text" name="last_name" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input type="text" name="phone" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">Address line 1</label><input type="text" name="address_line_1" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">Address line 2</label><input type="text" name="address_line_2" class="{{ $inputClass }}"></div>
            @include('admin.partials.location-select-fields', ['inputClass' => $inputClass])
            <div><label class="block text-xs text-gray-500 mb-1">ZIP</label><input type="text" name="zip_code" class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="inline-flex items-center gap-2 text-sm"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-primary"> Set as default</label></div>
            <div class="sm:col-span-2"><button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium">Add address</button></div>
        </form>
    </div>
</section>
@endsection
