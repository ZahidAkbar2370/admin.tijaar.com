@php
    $navItems = \App\Services\Admin\SellerAdminService::navItems();
    $btnBase = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition whitespace-nowrap border';
    $btnActive = 'bg-amber-500/10 text-amber-800 border-amber-200';
    $btnIdle = 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900';
@endphp

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <a href="{{ route('admin.sellers.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Private Sellers
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

<div class="mb-5 flex flex-wrap items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
        {{ strtoupper(substr($user->name, 0, 2)) }}
    </div>
    <div class="min-w-0">
        <p class="font-semibold text-gray-900 truncate">{{ $user->name }} <span class="text-gray-400 font-normal">#{{ $user->id }}</span></p>
        <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
    </div>
    @if ($user->seller?->store)
        <span class="ml-auto text-xs font-medium px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $user->seller->store->name }}</span>
    @endif
</div>
