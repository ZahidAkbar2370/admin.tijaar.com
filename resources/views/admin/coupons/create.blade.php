@extends('admin.layouts.app')

@section('title', 'Create Coupon')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Coupons
    </a>
</div>

<div class="w-full min-w-0">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Coupon</h1>

    <form method="POST" action="{{ route('admin.coupons.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ strtoupper(old('code')) }}" placeholder="SAVE10" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
                @error('code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                    <option value="percentage" {{ old('type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Value <span class="text-red-500">*</span></label>
                <input type="number" name="value" value="{{ old('value') }}" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
                @error('value') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Min order amount</label>
                <input type="number" name="min_order_amount" value="{{ old('min_order_amount', 0) }}" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Max discount</label>
                <input type="number" name="max_discount" value="{{ old('max_discount') }}" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl" placeholder="Optional" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Max uses</label>
                <input type="number" name="max_uses" value="{{ old('max_uses') }}" min="1" class="w-full px-4 py-3 border border-gray-200 rounded-xl" placeholder="Unlimited" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Valid from</label>
                <input type="datetime-local" name="valid_from" value="{{ old('valid_from') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Valid to</label>
                <input type="datetime-local" name="valid_to" value="{{ old('valid_to') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Scope</label>
            <select name="scope" id="scope" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                <option value="platform" {{ old('scope', 'platform') === 'platform' ? 'selected' : '' }}>Platform</option>
                <option value="store" {{ old('scope') === 'store' ? 'selected' : '' }}>Store</option>
            </select>
        </div>

        <div id="store-select" style="{{ old('scope', 'platform') === 'platform' ? 'display:none' : '' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Store</label>
            <select name="store_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                <option value="">Select store</option>
                @foreach ($stores as $s)
                <option value="{{ $s->id }}" {{ old('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Restrict to categories (optional)</label>
            <select name="category_ids[]" multiple class="w-full px-4 py-3 border border-gray-200 rounded-xl" style="min-height:100px">
                @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ in_array($cat->id, old('category_ids', [])) ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Leave empty = no restriction. Hold Ctrl to select multiple.</p>
        </div>

        <div class="pt-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition">Create Coupon</button>
            <a href="{{ route('admin.coupons.index') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
<script>
document.getElementById('scope').addEventListener('change', function() {
    document.getElementById('store-select').style.display = this.value === 'store' ? 'block' : 'none';
});
</script>
@endsection
