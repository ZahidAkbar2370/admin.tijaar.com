@extends('admin.layouts.app')

@section('title', 'Home Display')

@section('admin-content')
<div class="w-full min-w-0 max-w-4xl">
    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50/90 border border-emerald-200 rounded-2xl text-emerald-800 flex items-center gap-3 shadow-sm">
            <span class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Home Display</h1>
        <p class="text-gray-500 mt-2 max-w-xl">Choose which categories appear on the home page. All products from the selected categories will be shown in Browse Categories and Best Sellers.</p>
    </div>

    <form method="POST" action="{{ route('admin.home-featured.update') }}">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-br from-primary/5 via-white to-primary/5">
                <div class="flex items-start gap-4">
                    <span class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6h6m-3-3v6"/></svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Categories on Home</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Select one or more categories. They will appear in Browse Categories, and all their products in Best Sellers.</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                @if ($selectedCategories->isNotEmpty())
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Selected ({{ $selectedCategories->count() }})</p>
                    <div class="flex flex-wrap gap-2 mb-5">
                        @foreach ($selectedCategories as $c)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $c->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Choose categories</p>
                <div class="flex flex-wrap gap-2">
                    @forelse ($categories as $cat)
                        <label class="group relative inline-flex items-center gap-2.5 px-4 py-3 rounded-xl border-2 transition-all duration-200 cursor-pointer
                            {{ in_array($cat->id, $selectedCategoryIds) ? 'border-primary bg-primary/10 text-primary shadow-sm' : 'border-gray-200 hover:border-primary/40 hover:bg-gray-50/80 text-gray-700' }}">
                            <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" {{ in_array($cat->id, $selectedCategoryIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 w-4 h-4" />
                            <span class="text-sm font-medium select-none">{{ $cat->name }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 py-4">No active categories. <a href="{{ route('admin.categories.index') }}" class="text-primary hover:underline font-medium">Add categories</a> first.</p>
                    @endforelse
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium rounded-xl transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm hover:shadow transition focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2">
                    Save Home Display
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
