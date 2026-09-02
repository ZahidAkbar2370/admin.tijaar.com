@extends('admin.layouts.app')
@section('title', 'Account Actions — Customer #' . $user->id)
@section('admin-content')
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h1 class="text-lg font-bold text-gray-900 mb-4">Account Actions</h1>
    <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 text-sm text-gray-600 mb-4">
        @if ($user->is_banned) Account is <span class="font-semibold text-red-600">banned</span>.
        @elseif ($user->is_suspended) Account is <span class="font-semibold text-amber-600">suspended</span>.
        @else Account is <span class="font-semibold text-emerald-600">active</span>.@endif
    </div>
    <div class="flex flex-col gap-2">
        @if ($user->is_suspended)
            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">@csrf<button type="submit" class="w-full px-4 py-2.5 text-sm bg-emerald-100 text-emerald-700 rounded-xl font-medium">Unsuspend</button></form>
        @elseif (!$user->is_banned)
            <form method="POST" action="{{ route('admin.users.suspend', $user) }}">@csrf<button type="submit" class="w-full px-4 py-2.5 text-sm bg-amber-100 text-amber-700 rounded-xl font-medium">Suspend</button></form>
        @endif
        @if ($user->is_banned)
            <form method="POST" action="{{ route('admin.users.unban', $user) }}">@csrf<button type="submit" class="w-full px-4 py-2.5 text-sm bg-emerald-100 text-emerald-700 rounded-xl font-medium">Unban</button></form>
        @else
            <form method="POST" action="{{ route('admin.users.ban', $user) }}" onsubmit="return confirm('Ban this customer?');">@csrf<button type="submit" class="w-full px-4 py-2.5 text-sm bg-red-100 text-red-700 rounded-xl font-medium">Ban</button></form>
        @endif
    </div>
</section>
@endsection
