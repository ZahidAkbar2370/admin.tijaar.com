@extends('admin.layouts.app')

@section('title', 'Pages')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Pages</h1>
    <p class="text-sm text-gray-500 mt-1">Edit content of your public site pages. Each card below is a page visitors can see—click Edit to update text and images.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
    <form method="GET" action="{{ route('admin.pages.index') }}" class="mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or slug..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">Search</button>
            @if (request('search'))
                <a href="{{ route('admin.pages.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
            @endif
        </div>
    </form>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
    @forelse ($pages as $p)
        <div class="group relative bg-gradient-to-br from-gray-50 to-white rounded-xl border border-gray-100 p-5 hover:border-primary/20 hover:shadow-md transition-all duration-200">
            <div class="flex flex-col h-full min-h-[120px]">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2 mb-1">{{ $p->title }}</h3>
                    <p class="text-xs text-gray-500 font-mono">/{{ $p->slug }}</p>
                    @if ($p->is_active)
                        <span class="inline-flex items-center gap-1 mt-2 text-xs text-emerald-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 mt-2 text-xs text-gray-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Hidden
                        </span>
                    @endif
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="{{ route('admin.pages.edit', $p) }}" class="inline-flex items-center justify-center gap-2 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-primary bg-primary/10 hover:bg-primary/20 border border-primary/20 hover:border-primary/30 transition shadow-sm" title="Edit this page">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full p-16 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-gray-500 font-medium">No pages found</p>
            <p class="text-sm text-gray-400 mt-1">Run the CMS seeder to create default pages (About Us, Terms, Privacy, etc.)</p>
        </div>
        @endforelse
    </div>

    @if ($pages->hasPages())
    @include('admin.partials.pagination-arrows', ['paginator' => $pages])
    @endif
</div>
@endsection
