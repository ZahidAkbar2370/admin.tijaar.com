@extends('admin.layouts.app')

@section('title', $config['label'])

@section('admin-content')
@php
    $toggles = array_filter($config['fields'], fn ($f) => ($f['type'] ?? 'text') === 'toggle');
    $inputs = array_filter($config['fields'], fn ($f) => ($f['type'] ?? 'text') !== 'toggle');
@endphp
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">{{ $config['label'] }}</h1>
    <p class="text-sm text-gray-500 mb-6">
        {{ $config['description'] }}
        @if (!empty($config['docs_url']))
            <a href="{{ $config['docs_url'] }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $config['docs_label'] ?? 'Documentation' }}</a>.
        @endif
    </p>

    @include('admin.payment-methods.partials.nav')
    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.payment-methods.' . $method . '.update') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="pb-6 border-b border-gray-100">
            @include('admin.partials.brand-logo-field', [
                'inputName' => 'logo',
                'logoUrl' => $logo_url,
                'removeName' => $has_custom_logo ? 'remove_logo' : null,
                'help' => 'Shown at checkout when this payment method is enabled. Upload your own logo or keep the default.',
            ])
        </div>

        @if ($toggles !== [])
            <div class="divide-y divide-gray-100 border-b border-gray-100">
                @foreach ($toggles as $key => $field)
                    @include('admin.partials.setting-toggle', [
                        'name' => 'settings[' . $key . ']',
                        'value' => $settings[$key],
                        'label' => $field['label'],
                        'help' => $field['help'] ?? '',
                    ])
                @endforeach
            </div>
        @endif

        @if ($inputs !== [])
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($inputs as $key => $field)
                    @php
                        $type = $field['type'] ?? 'text';
                        $isSecret = $type === 'password';
                        $hasStored = ($settings[$key] ?? '') !== '';
                        $attrs = $field['attrs'] ?? [];
                    @endphp
                    <div class="{{ in_array($type, ['url', 'select'], true) || ($field['wide'] ?? false) ? 'sm:col-span-2' : '' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $field['label'] }}</label>
                        @if ($type === 'select')
                            <select name="settings[{{ $key }}]"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                @foreach ($field['options'] as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" {{ old('settings.' . $key, $settings[$key]) === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($isSecret)
                            <input type="password" name="settings[{{ $key }}]" autocomplete="new-password"
                                   placeholder="{{ $hasStored ? '•••••••• (saved)' : ($field['placeholder'] ?? '') }}"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @else
                            <input type="{{ $type === 'number' ? 'number' : ($type === 'url' ? 'url' : 'text') }}"
                                   name="settings[{{ $key }}]" value="{{ old('settings.' . $key, $settings[$key]) }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   @foreach ($attrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @endif
                        @if (!empty($field['help']))
                            <p class="text-xs text-gray-500 mt-1">{{ $field['help'] }}</p>
                        @endif
                        @if ($isSecret && $hasStored)
                            <p class="text-xs text-gray-400 mt-1">Leave blank to keep the saved value.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($method === 'cod')
            @php $pct = max(1, min(99, (int) ($settings['partial_payment_online_percent'] ?: 50))); @endphp
            <div class="p-4 bg-sky-50 border border-sky-100 rounded-xl text-sm text-sky-900">
                <strong>Partial payment preview:</strong> customer pays <strong>{{ $pct }}%</strong> online and <strong>{{ 100 - $pct }}%</strong> cash on delivery.
            </div>
        @endif

        @if ($method === 'jazzcash')
            <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-950 space-y-3">
                <div>
                    <p class="font-semibold mb-1">Return URL (browser redirect after payment)</p>
                    <p class="font-mono text-xs break-all select-all">{{ \App\Services\JazzCashService::recommendedReturnUrl() }}</p>
                </div>
                <div>
                    <p class="font-semibold mb-1">IPN URL (mandatory — register in the JazzCash portal)</p>
                    <p class="font-mono text-xs break-all select-all">{{ \App\Services\JazzCashService::recommendedIpnUrl() }}</p>
                </div>
                <p class="text-xs text-amber-900">Register both URLs in the JazzCash merchant portal Credentials tab.</p>
            </div>
        @endif

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
                Save {{ $config['label'] }} Settings
            </button>
        </div>
    </form>

    @if ($method === 'jazzcash')
        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6" x-data="{
            loading: false,
            message: '',
            isSuccess: false,
            fieldValue(name) {
                const el = document.getElementsByName(name)[0];
                return el ? el.value : '';
            },
            test() {
                this.loading = true;
                this.message = '';
                fetch('{{ route('admin.payment-methods.jazzcash.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        merchant_id: this.fieldValue('settings[jazzcash_merchant_id]'),
                        password: this.fieldValue('settings[jazzcash_password]'),
                        integrity_salt: this.fieldValue('settings[jazzcash_integrity_salt]'),
                        checkout_url: this.fieldValue('settings[jazzcash_checkout_url]'),
                        return_url: this.fieldValue('settings[jazzcash_return_url]'),
                    }),
                })
                    .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        this.loading = false;
                        this.isSuccess = ok && data.success === true;
                        this.message = data.message || (data.success ? 'Configuration looks good.' : 'Test failed.');
                    })
                    .catch(() => {
                        this.loading = false;
                        this.isSuccess = false;
                        this.message = 'Request failed. Check your connection and try again.';
                    });
            }
        }">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Test JazzCash configuration</h2>
            <p class="text-xs text-gray-500 mb-4">Validates the credentials and URLs above. No payment is charged.</p>
            <button type="button" @click="test()" :disabled="loading"
                    class="px-4 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-medium hover:bg-gray-700 transition disabled:opacity-50">
                <span x-text="loading ? 'Testing…' : 'Test connection'"></span>
            </button>
            <div x-show="message" x-cloak class="mt-4 p-3 rounded-xl text-sm"
                 :class="isSuccess ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
                <span x-text="message"></span>
            </div>
        </div>
    @endif
</div>
@endsection
