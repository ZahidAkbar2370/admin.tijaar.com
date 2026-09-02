@extends('admin.layouts.app')
@section('title', 'Edit Profile — Customer #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary'; @endphp
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h1 class="text-lg font-bold text-gray-900 mb-4">Edit Profile</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Phone</label><input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="03XXXXXXXXX" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">WhatsApp Number</label><input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" placeholder="03XXXXXXXXX" class="{{ $inputClass }}"></div>
        </div>
        <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-3 py-2"><input type="checkbox" name="email_verified" value="1" {{ old('email_verified', $user->email_verified_at) ? 'checked' : '' }} class="rounded border-gray-300 text-primary"> Email verified</label>
            <label class="inline-flex items-center gap-2 text-sm bg-gray-50 border border-gray-100 rounded-xl px-3 py-2"><input type="checkbox" name="whatsapp_verified" value="1" {{ old('whatsapp_verified', $user->whatsapp_verified_at) ? 'checked' : '' }} class="rounded border-gray-300 text-primary"> WhatsApp verified</label>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-dark">Save profile</button>
    </form>
</section>
@endsection
