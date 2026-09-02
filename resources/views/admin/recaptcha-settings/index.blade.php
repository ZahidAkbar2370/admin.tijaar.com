@extends('admin.layouts.app')

@section('title', 'Google reCAPTCHA')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Google reCAPTCHA</h1>
    <p class="text-sm text-gray-500 mb-6">
        Protect customer login and registration with Google reCAPTCHA v2 (“I’m not a robot”).
        Create keys at
        <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">google.com/recaptcha/admin</a>
        (choose <strong>reCAPTCHA v2 → “I’m not a robot” Checkbox</strong>).
    </p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.recaptcha-settings.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
        @csrf

        @include('admin.partials.setting-toggle', [
            'name' => 'recaptcha_enabled',
            'label' => 'Enable Google reCAPTCHA',
            'help' => 'Master switch. Requires both Site Key and Secret Key. When off, login and register work without captcha.',
            'value' => $settings['recaptcha_enabled'],
        ])

        <div class="pt-4 border-t border-gray-100 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site Key (public)</label>
                <input type="text" name="recaptcha_site_key" value="{{ old('recaptcha_site_key', $settings['recaptcha_site_key']) }}"
                       placeholder="6Lc…"
                       autocomplete="off"
                       class="w-full max-w-xl px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">Shown to customers on login/register. Safe to expose in the browser.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key (private)</label>
                <input type="password" name="recaptcha_secret_key" value=""
                       placeholder="{{ $settings['recaptcha_secret_key'] ? '••••••••  (leave blank to keep current)' : '6Lc…' }}"
                       autocomplete="new-password"
                       class="w-full max-w-xl px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">
                    Used only on the server to verify tokens. Never shown on the public site.
                    @if($settings['recaptcha_secret_key'])
                        A secret is already saved — leave blank to keep it.
                    @endif
                </p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 space-y-1">
            <p class="text-sm font-semibold text-gray-900 mb-2">Where to require captcha</p>
            @include('admin.partials.setting-toggle', [
                'name' => 'recaptcha_on_login',
                'label' => 'Require on Login',
                'help' => 'Show and verify reCAPTCHA on the customer login form.',
                'value' => $settings['recaptcha_on_login'],
            ])
            @include('admin.partials.setting-toggle', [
                'name' => 'recaptcha_on_register',
                'label' => 'Require on Register',
                'help' => 'Show and verify reCAPTCHA on the customer registration form.',
                'value' => $settings['recaptcha_on_register'],
            ])
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
                Save reCAPTCHA Settings
            </button>
        </div>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 space-y-2">
        <p class="font-medium">Setup tips</p>
        <ul class="list-disc pl-5 text-slate-600 space-y-1">
            <li>Add your live domain (and <code class="text-xs bg-white px-1 rounded">localhost</code> for local testing) in the Google reCAPTCHA admin domains list.</li>
            <li>Use <strong>reCAPTCHA v2 Checkbox</strong> keys — v3 keys will not work with this integration.</li>
            <li>After enabling, open Login/Register on the storefront and confirm the widget appears before saving credentials in production.</li>
        </ul>
    </div>
</div>
@endsection
