@extends('admin.layouts.app')

@section('title', 'Profile — Seller #' . $user->id)

@section('admin-content')

@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500'; @endphp

@include('admin.sellers.partials.seller-nav')

<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">

    <h1 class="text-xl font-bold text-gray-900 mb-1">Edit Profile</h1>

    <p class="text-sm text-gray-500 mb-2">Email, phone, WhatsApp, city, and province are shared with the public store. Manage them here only.</p>

    <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 mb-6">Changing email, phone, or WhatsApp clears that verification until you check the verify box and save again.</p>

    <form method="POST" action="{{ route('admin.sellers.update', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        @csrf @method('PUT')

        <input type="hidden" name="_return" value="profile">

        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $inputClass }}"></div>

        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $inputClass }}"></div>

        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Phone</label><input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="03XXXXXXXXX" class="{{ $inputClass }}"></div>

        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">WhatsApp number</label><input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" placeholder="03XXXXXXXXX" class="{{ $inputClass }}"></div>

        @include('admin.partials.location-select-fields', [

            'inputClass' => $inputClass,

            'countryValue' => \App\Services\LocationService::defaultCountryName(),

            'stateValue' => old('state', $user->state),

            'cityValue' => old('city', $user->city),

            'required' => false,

            'gridClass' => 'sm:col-span-2 lg:col-span-3 contents',

        ])

        <div class="sm:col-span-2 lg:col-span-3">

            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Verification</p>

            <div class="flex flex-wrap gap-3">

                <label class="inline-flex items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">

                    <input type="checkbox" name="email_verified" value="1" {{ old('email_verified', $user->email_verified_at) ? 'checked' : '' }} class="rounded border-gray-300 text-amber-600">

                    Email verified

                    @if ($user->email_verified_at)<span class="text-[10px] text-emerald-600 font-medium">({{ $user->email_verified_at->format('M d, Y') }})</span>@endif

                </label>

                <label class="inline-flex items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">

                    <input type="checkbox" name="phone_verified" value="1" {{ old('phone_verified', $user->phone_verified_at) ? 'checked' : '' }} class="rounded border-gray-300 text-amber-600">

                    Phone verified

                    @if ($user->phone_verified_at)<span class="text-[10px] text-emerald-600 font-medium">({{ $user->phone_verified_at->format('M d, Y') }})</span>@endif

                </label>

                <label class="inline-flex items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">

                    <input type="checkbox" name="whatsapp_verified" value="1" {{ old('whatsapp_verified', $user->whatsapp_verified_at) ? 'checked' : '' }} class="rounded border-gray-300 text-amber-600">

                    WhatsApp verified

                    @if ($user->whatsapp_verified_at)<span class="text-[10px] text-emerald-600 font-medium">({{ $user->whatsapp_verified_at->format('M d, Y') }})</span>@endif

                </label>

            </div>

        </div>

        <div class="sm:col-span-2 lg:col-span-3"><button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-medium">Save profile</button></div>

    </form>

</section>

@endsection

