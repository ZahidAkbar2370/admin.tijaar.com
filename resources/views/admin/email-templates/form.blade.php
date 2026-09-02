@extends('admin.layouts.app')

@section('title', 'Edit: ' . $emailTemplate->name)

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.email-settings.partials.nav')

    <div class="mb-8">
        <a href="{{ route('admin.email-templates.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Email Templates
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: {{ $emailTemplate->name }}</h1>
        <p class="text-gray-500 mt-1">Change the subject and body of this automated email. Use placeholders like &#123;&#123;name&#125;&#125;, &#123;&#123;order_number&#125;&#125;, &#123;&#123;app_name&#125;&#125; where supported.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    Template info
                </h2>
                <p class="text-sm text-gray-500 mt-1">Name and when this email is sent (for your reference).</p>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $emailTemplate->name) }}" required placeholder="e.g. Order Confirmation" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    <p class="text-xs text-gray-500 mt-1">Display name in the admin list. Does not affect the email recipients see.</p>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                @if ($emailTemplate->description)
                    <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl">{{ $emailTemplate->description }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    Subject &amp; body (HTML)
                </h2>
                <p class="text-sm text-gray-500 mt-1">Subject line and HTML content. Use placeholders as described for this template.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject line <span class="text-red-500">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject', $emailTemplate->subject) }}" required placeholder="e.g. Your order &#123;&#123;order_number&#125;&#125; is confirmed" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Body (HTML)</label>
                        <textarea name="body_html" rows="14" placeholder="<p>Hello &#123;&#123;name&#125;&#125;,</p><p>Your order has been confirmed.</p>" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('body_html', $emailTemplate->body_html) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Valid HTML. Placeholders are replaced when the email is sent. Logo and footer are added automatically.</p>
                        @error('body_html')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    Plain text fallback
                </h2>
                <p class="text-sm text-gray-500 mt-1">Optional. Shown when the email client does not support HTML.</p>
            </div>
            <div class="p-6">
                <textarea name="body_plain" rows="6" placeholder="Hello &#123;&#123;name&#125;&#125;,&#10;Your order &#123;&#123;order_number&#125;&#125; has been confirmed." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('body_plain', $emailTemplate->body_plain) }}</textarea>
                @error('body_plain')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    </span>
                    Admin-only note
                </h2>
                <p class="text-sm text-gray-500 mt-1">Internal description (not sent in the email).</p>
            </div>
            <div class="p-6">
                <input type="text" name="description" value="{{ old('description', $emailTemplate->description) }}" placeholder="e.g. Sent when customer places an order" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm transition">Save template</button>
            <a href="{{ route('admin.email-templates.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
