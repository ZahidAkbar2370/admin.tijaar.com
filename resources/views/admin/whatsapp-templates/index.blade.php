@extends('admin.layouts.app')

@section('title', 'WhatsApp Templates')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="w-full min-w-0">
    @include('admin.wachat-settings.partials.nav')

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp Templates</h1>
            <p class="text-sm text-gray-500 mt-1">Edit WaChat message text for orders, payments, and OTP. Use placeholders like &#123;&#123;order_number&#125;&#125;.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse ($templates as $t)
            <div class="p-4 flex justify-between items-start gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-gray-900">{{ $t->name }}</p>
                        @if (! $t->is_active)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $t->description }}</p>
                    <p class="text-xs text-gray-400 mt-1">Slug: <code class="bg-gray-100 px-1 rounded">{{ $t->slug }}</code>
                        @if ($t->event_key)
                            · Event: <code class="bg-gray-100 px-1 rounded">{{ $t->event_key }}</code>
                        @endif
                    </p>
                    <p class="text-xs text-gray-600 mt-2 line-clamp-2 font-mono bg-gray-50 rounded-lg px-2 py-1.5">{{ $t->body }}</p>
                </div>
                <a href="{{ route('admin.whatsapp-templates.edit', $t) }}" class="flex-shrink-0 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg">Edit</a>
            </div>
            @empty
            <div class="p-16 text-center text-gray-500">
                No WhatsApp templates. Run
                <code class="bg-gray-100 px-2 py-1 rounded">php artisan db:seed --class=WhatsappTemplateSeeder</code>
                to create defaults.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
