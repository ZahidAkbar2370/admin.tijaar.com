@extends('admin.layouts.app')

@section('title', 'Email Events')

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.email-settings.partials.nav')

    <h1 class="text-xl font-bold text-gray-900 mb-1">Email Events</h1>
    <p class="text-sm text-gray-500 mb-6">Control registration verification and which automated emails are sent. Edit content under Email Templates.</p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.email-settings.events.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
        @csrf

        <div class="divide-y divide-gray-100">
            @include('admin.partials.setting-toggle', [
                'name' => 'email_verification_required',
                'value' => $settings['email_verification_required'],
                'label' => 'Require email verification after registration',
                'help' => 'When enabled, customers must verify via the email OTP before login or any account actions. Google/Facebook sign-in marks email as verified automatically. When disabled, accounts work immediately after signup.',
            ])
            @include('admin.partials.setting-toggle', [
                'name' => 'email_welcome_enabled',
                'value' => $settings['email_welcome_enabled'],
                'label' => 'Welcome email',
                'help' => 'Send a welcome message after successful registration.',
            ])
            @include('admin.partials.setting-toggle', [
                'name' => 'email_password_reset_enabled',
                'value' => $settings['email_password_reset_enabled'],
                'label' => 'Password reset email',
                'help' => 'Send reset links when a user requests a new password.',
            ])
            @include('admin.partials.setting-toggle', [
                'name' => 'email_order_placed_enabled',
                'value' => $settings['email_order_placed_enabled'],
                'label' => 'Order placed email',
                'help' => 'Notify the customer when an order is placed.',
            ])
            @include('admin.partials.setting-toggle', [
                'name' => 'email_order_shipped_enabled',
                'value' => $settings['email_order_shipped_enabled'],
                'label' => 'Order shipped email',
                'help' => 'Notify the customer when an order is marked shipped.',
            ])
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save Email Events</button>
            <a href="{{ route('admin.email-templates.index') }}" class="ml-3 text-sm text-gray-500 hover:text-primary">Email templates →</a>
        </div>
    </form>
</div>
@endsection
