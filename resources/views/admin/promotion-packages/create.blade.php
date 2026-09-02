@extends('admin.layouts.app')

@section('title', 'Create Promotion Package')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.promotion-packages.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Packages
    </a>
</div>

<div class="w-full min-w-0">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Promotion Package</h1>

    <form method="POST" action="{{ route('admin.promotion-packages.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                <option value="featured_product" {{ old('type') === 'featured_product' ? 'selected' : '' }}>Featured Product</option>
                <option value="hot_sale" {{ old('type') === 'hot_sale' ? 'selected' : '' }}>Hot Sale / Flash Deal</option>
                <option value="featured_shop" {{ old('type') === 'featured_shop' ? 'selected' : '' }}>Featured Shop</option>
                <option value="store_banner" {{ old('type') === 'store_banner' ? 'selected' : '' }}>Store Banner</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (PKR) <span class="text-red-500">*</span></label>
                <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration (days) <span class="text-red-500">*</span></label>
                <input type="number" name="duration_days" value="{{ old('duration_days', 7) }}" min="1" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
        </div>

        <div class="pt-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition">Create Package</button>
            <a href="{{ route('admin.promotion-packages.index') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
