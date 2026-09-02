@extends('admin.layouts.app')

@section('title', 'Store — Seller #' . $user->id)

@section('admin-content')

@php

    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500';

    $store = $user->seller?->store;

@endphp

@include('admin.sellers.partials.seller-nav')

<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">

    <h1 class="text-xl font-bold text-gray-900 mb-1">Store</h1>

    <p class="text-sm text-gray-500 mb-6">Public storefront details. Phone, email, city, and province come from the seller profile — edit them on the <a href="{{ route('admin.sellers.profile', $user) }}" class="text-amber-700 hover:underline font-medium">Profile</a> tab.</p>

    @if ($store)

    <form method="POST" action="{{ route('admin.sellers.update', $user) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        @csrf @method('PUT')

        <input type="hidden" name="_return" value="storefront">



        <div class="sm:col-span-2 rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm text-gray-600">

            <p class="font-semibold text-gray-800 mb-2">From profile (read-only here)</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">

                <p>Email: {{ $user->email ?? '—' }}</p>

                <p>Phone: {{ $user->phone ?? '—' }} @if($user->phone_verified_at)<span class="text-emerald-600 text-xs">· verified</span>@endif</p>

                <p>WhatsApp: {{ $user->whatsapp_number ?? '—' }} @if($user->whatsapp_verified_at)<span class="text-emerald-600 text-xs">· verified</span>@endif</p>

                <p>Province: {{ $user->state ?? '—' }}</p>

                <p>City: {{ $user->city ?? '—' }}</p>

                <p>Country: {{ \App\Services\LocationService::defaultCountryName() }}</p>

            </div>

        </div>



        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>

                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Profile image (logo)</label>

                @if ($store->logo)

                    <img src="{{ \App\Support\UploadHelper::url($store->logo) }}" alt="{{ $store->logo_alt ?: $store->name }}" class="w-24 h-24 rounded-xl object-cover border border-gray-200 mb-3">

                @else

                    <div class="w-24 h-24 rounded-xl bg-gray-100 border border-dashed border-gray-200 flex items-center justify-center text-xs text-gray-400 mb-3">No image</div>

                @endif

                <input type="file" name="logo" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="w-full text-sm">

                <input type="text" name="logo_alt" value="{{ old('logo_alt', $store->logo_alt) }}" placeholder="Image alt text (optional)" class="{{ $inputClass }} mt-2">

            </div>

            <div>

                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Cover photo</label>

                @if ($store->cover_image)

                    <img src="{{ \App\Support\UploadHelper::url($store->cover_image) }}" alt="{{ $store->cover_image_alt ?: $store->name }}" class="w-full h-28 rounded-xl object-cover border border-gray-200 mb-3">

                @else

                    <div class="w-full h-28 rounded-xl bg-gray-100 border border-dashed border-gray-200 flex items-center justify-center text-xs text-gray-400 mb-3">No cover</div>

                @endif

                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="w-full text-sm">

                <input type="text" name="cover_image_alt" value="{{ old('cover_image_alt', $store->cover_image_alt) }}" placeholder="Cover alt text (optional)" class="{{ $inputClass }} mt-2">

            </div>

        </div>



        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Store name</label><input type="text" name="store_name" value="{{ old('store_name', $store->name) }}" required class="{{ $inputClass }}"></div>

        <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Address</label><input type="text" name="store_address" value="{{ old('store_address', $store->address) }}" class="{{ $inputClass }}"></div>

        <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Description</label><textarea name="store_description" rows="4" class="{{ $inputClass }}">{{ old('store_description', $store->description) }}</textarea></div>

        <div class="sm:col-span-2"><button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-medium">Save store</button></div>

    </form>

    @else

    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-10 text-center text-gray-500">No store yet. Register via Register Business or approve seller setup.</div>

    @endif

</section>

@endsection

