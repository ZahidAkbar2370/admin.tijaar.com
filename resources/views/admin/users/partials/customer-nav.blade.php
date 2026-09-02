@php
    $navItems = \App\Services\Admin\CustomerAdminService::navItems();
    $btnBase = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition whitespace-nowrap border';
    $btnActive = 'bg-primary/10 text-primary border-primary/20';
    $btnIdle = 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900';
@endphp

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Customers
    </a>
    <div class="flex flex-wrap gap-2 lg:justify-end">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route'], $user) }}"
               class="{{ $btnBase }} {{ request()->routeIs($item['active']) ? $btnActive : $btnIdle }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>

@include('admin.partials.settings-flash')

<div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-500">
    <span class="font-semibold text-gray-900">{{ $user->name }}</span>
    <span>·</span>
    <span>#{{ $user->id }}</span>
    <span>·</span>
    <span>{{ $user->email }}</span>
</div>
