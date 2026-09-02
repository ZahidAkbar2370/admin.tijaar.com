@extends('admin.layouts.app')

@section('title', 'WaChat API')

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.wachat-settings.partials.nav')

    <h1 class="text-xl font-bold text-gray-900 mb-1">WaChat API</h1>
    <p class="text-sm text-gray-500 mb-6">
        Connect Waghl WhatsApp API credentials. Messages are only sent when WhatsApp is enabled here
        and the customer/seller has verified their number in profile.
    </p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.wachat-settings.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
        @csrf

        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_enabled',
            'label' => 'Enable WaChat WhatsApp',
            'help' => 'Master switch. Requires API key, sender number, and endpoint. When off, no WhatsApp messages are sent.',
            'value' => $settings['wachat_enabled'],
        ])

        @include('admin.partials.setting-toggle', [
            'name' => 'notification_whatsapp_enabled',
            'label' => 'Allow WhatsApp in user notification preferences',
            'help' => 'When on, customers/sellers can enable or disable WhatsApp order/listing alerts in Profile → Notifications. When off, WhatsApp prefs are hidden and event messages still respect WaChat event toggles + verification.',
            'value' => $settings['notification_whatsapp_enabled'] ?? '1',
        ])

        <div class="pt-4 border-t border-gray-100 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                <input type="password" name="wachat_api_key" value=""
                       placeholder="{{ $settings['wachat_api_key'] ? '••••••••  (leave blank to keep current)' : 'API_KEY_xxx' }}"
                       autocomplete="new-password"
                       class="w-full max-w-xl px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">
                    Waghl API key used in the request body.
                    @if($settings['wachat_api_key'])
                        A key is already saved — leave blank to keep it.
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sender number</label>
                <input type="text" name="wachat_sender" value="{{ old('wachat_sender', $settings['wachat_sender']) }}"
                       placeholder="923XXXXXXXXX or 03XXXXXXXXX"
                       autocomplete="off"
                       class="w-full max-w-xl px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">WhatsApp business/sender number registered with Waghl (international digits preferred, e.g. 923…). Stored numbers for customers use 03… format.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API endpoint</label>
                <input type="url" name="wachat_api_endpoint" value="{{ old('wachat_api_endpoint', $settings['wachat_api_endpoint']) }}"
                       placeholder="https://custom2.waghl.com/send-message"
                       autocomplete="off"
                       class="w-full max-w-xl px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">POST endpoint for send-message.</p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
                Save API Settings
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.wachat-settings.test') }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        @csrf
        <div>
            <p class="text-sm font-semibold text-gray-900">Send test message</p>
            <p class="text-xs text-gray-500 mt-1">Uses the saved API key / sender / endpoint. Destination must be a real WhatsApp number (03… or 923…).</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" name="test_phone" value="{{ old('test_phone') }}" required
                       placeholder="03XXXXXXXXX"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message (optional)</label>
                <input type="text" name="test_message" value="{{ old('test_message') }}"
                       placeholder="Tijaar WaChat test…"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
        </div>
        <button type="submit" class="px-5 py-2.5 border border-primary text-primary hover:bg-primary/5 font-medium rounded-xl text-sm transition">
            Send test WhatsApp
        </button>
    </form>

    <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 space-y-2">
        <p class="font-medium">How it works</p>
        <ul class="list-disc pl-5 text-slate-600 space-y-1">
            <li>Customers and sellers verify WhatsApp from their profile (OTP sent via this API to their phone number).</li>
            <li>If WhatsApp is not verified, event messages for that user are skipped even when the event toggle is on.</li>
            <li>Request body: <code class="text-xs bg-white px-1 rounded">api_key</code>, <code class="text-xs bg-white px-1 rounded">sender</code>, <code class="text-xs bg-white px-1 rounded">number</code>, <code class="text-xs bg-white px-1 rounded">message</code>.</li>
            <li>Check <code class="text-xs bg-white px-1 rounded">storage/logs/laravel.log</code> for lines starting with <code class="text-xs bg-white px-1 rounded">WaChat</code> if a message did not arrive.</li>
        </ul>
    </div>
</div>
@endsection
