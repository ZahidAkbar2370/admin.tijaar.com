@extends('admin.layouts.app')
@section('title', 'Alerts — Customer #' . $user->id)
@section('admin-content')
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h1 class="text-lg font-bold text-gray-900 mb-1">Alerts Preferences</h1>
    <p class="text-sm text-gray-500 mb-6">Email, WhatsApp, and Firebase alerts for website (browser) and mobile app.</p>
    <form method="POST" action="{{ route('admin.users.notifications.update', $user) }}" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @csrf
        @include('admin.partials.notification-prefs-fields', ['whatsappChannelOn' => $whatsappChannelOn ?? true])
        <div class="lg:col-span-2">
            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium">Save preferences</button>
        </div>
    </form>
</section>
@endsection
