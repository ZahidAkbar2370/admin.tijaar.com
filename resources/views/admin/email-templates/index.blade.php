@extends('admin.layouts.app')

@section('title', 'Email Templates')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="w-full min-w-0">
    @include('admin.email-settings.partials.nav')

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Email Templates</h1>
            <p class="text-sm text-gray-500 mt-1">Edit content of all auto-sent emails (welcome, OTP, order, payout, etc.)</p>
        </div>
    </div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="divide-y divide-gray-100">
        @forelse ($templates as $t)
        <div class="p-4 flex justify-between items-start gap-4">
            <div class="min-w-0">
                <p class="font-medium text-gray-900">{{ $t->name }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $t->description }}</p>
                <p class="text-xs text-gray-400 mt-1">Slug: <code class="bg-gray-100 px-1 rounded">{{ $t->slug }}</code></p>
            </div>
            <a href="{{ route('admin.email-templates.edit', $t) }}" class="flex-shrink-0 px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg">Edit</a>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No email templates. Run <code class="bg-gray-100 px-2 py-1 rounded">php artisan db:seed --class=EmailTemplateSeeder</code> to create defaults.</div>
        @endforelse
    </div>
</div>
</div>
@endsection
