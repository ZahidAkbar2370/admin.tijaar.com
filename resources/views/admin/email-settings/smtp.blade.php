@extends('admin.layouts.app')

@section('title', 'SMTP')

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.email-settings.partials.nav')

    <h1 class="text-xl font-bold text-gray-900 mb-1">SMTP</h1>
    <p class="text-sm text-gray-500 mb-6">Mail server used for OTPs, order emails, and password resets.</p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.email-settings.smtp.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mailer</label>
                <input type="text" name="mail_mailer" value="{{ old('mail_mailer', $settings['mail_mailer']) }}"
                       placeholder="smtp"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}"
                       placeholder="smtp.example.com"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}"
                       placeholder="465"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                <select name="mail_encryption" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    @php $enc = old('mail_encryption', $settings['mail_encryption']); @endphp
                    <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL (port 465)</option>
                    <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS (port 587)</option>
                    <option value="" {{ $enc === '' || $enc === null ? 'selected' : '' }}>None</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" autocomplete="off">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="mail_password" value=""
                       placeholder="{{ $settings['mail_password'] ? 'Leave blank to keep current' : '' }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" autocomplete="new-password">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep the saved password.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From address</label>
                <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                       placeholder="noreply@example.com"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From name</label>
                <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                       placeholder="Tijaar"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Save SMTP Settings</button>
        </div>
    </form>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
         x-data="{
            testEmail: '',
            loading: false,
            message: '',
            isSuccess: false,
            sendTest() {
                if (!this.testEmail.trim()) { this.message = 'Please enter an email address.'; this.isSuccess = false; return; }
                this.loading = true; this.message = '';
                fetch('{{ route('admin.email-settings.test-email') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ email: this.testEmail.trim() })
                }).then(r => r.json().then(data => ({ ok: r.ok, data }))).then(({ ok, data }) => {
                    this.loading = false;
                    this.isSuccess = ok && data.success === true;
                    var msg = data.message;
                    if (!msg && data.errors && data.errors.email) msg = data.errors.email[0];
                    this.message = msg || (data.success ? 'Email sent.' : 'Something went wrong.');
                }).catch(() => {
                    this.loading = false;
                    this.isSuccess = false;
                    this.message = 'Request failed. Please try again.';
                });
            }
         }">
        <h2 class="text-base font-semibold text-gray-900 mb-1">Test SMTP</h2>
        <p class="text-xs text-gray-500 mb-4">Save your SMTP settings above first, then send a test message.</p>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                <input type="email" x-model="testEmail" placeholder="e.g. you@example.com"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <button type="button" @click="sendTest()" :disabled="loading"
                    class="px-4 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-medium hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="loading ? 'Sending…' : 'Send email'"></span>
            </button>
        </div>
        <div x-show="message" x-cloak class="mt-3 p-3 rounded-xl text-sm"
             :class="isSuccess ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
             x-transition>
            <span x-text="message"></span>
        </div>
    </div>
</div>
@endsection
