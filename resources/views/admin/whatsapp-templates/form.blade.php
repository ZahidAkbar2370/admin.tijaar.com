@extends('admin.layouts.app')

@section('title', 'Edit: ' . $whatsappTemplate->name)

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.wachat-settings.partials.nav')

    <div class="mb-8">
        <a href="{{ route('admin.whatsapp-templates.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to WhatsApp Templates
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: {{ $whatsappTemplate->name }}</h1>
        <p class="text-gray-500 mt-1">Update the WhatsApp message body. Keep it short — WhatsApp works best under ~300 characters.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.whatsapp-templates.update', $whatsappTemplate) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Template info</h2>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $whatsappTemplate->name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                @if ($whatsappTemplate->description)
                    <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl">{{ $whatsappTemplate->description }}</p>
                @endif
                <p class="text-xs text-gray-500">Slug: <code class="bg-gray-100 px-1 rounded">{{ $whatsappTemplate->slug }}</code>
                    @if ($whatsappTemplate->event_key)
                        · Linked event: <code class="bg-gray-100 px-1 rounded">{{ $whatsappTemplate->event_key }}</code>
                    @endif
                </p>
                <label class="inline-flex items-center gap-2 text-sm text-gray-800 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-primary"
                           {{ old('is_active', $whatsappTemplate->is_active) ? 'checked' : '' }}>
                    Active (send this template when the event fires)
                </label>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Message body</h2>
                <p class="text-sm text-gray-500 mt-1">Plain text only. Placeholders are replaced when the message is sent.</p>
            </div>
            <div class="p-6">
                <textarea name="body" rows="8" required
                          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"
                          placeholder="Tijaar: Your order &#123;&#123;order_number&#125;&#125; …">{{ old('body', $whatsappTemplate->body) }}</textarea>
                @error('body')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-500 mt-2">Max 4000 characters. Prefer short messages for WhatsApp.</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-white font-medium rounded-xl transition">Save template</button>
            <a href="{{ route('admin.whatsapp-templates.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
