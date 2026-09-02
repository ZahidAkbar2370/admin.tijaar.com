@extends('admin.layouts.app')

@section('title', 'Add Commission Rule')

@section('admin-content')
<a href="{{ route('admin.commissions.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Back to Commission Rules
</a>

<h1 class="text-2xl font-bold text-gray-900 mb-6">Add Commission Rule</h1>

<form method="POST" action="{{ route('admin.commissions.store') }}" class="w-full min-w-0 space-y-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    @csrf

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Scope</label>
        <select name="scope_type" id="scope_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" required>
            <option value="global" {{ old('scope_type') === 'global' ? 'selected' : '' }}>Global (default)</option>
            <option value="category" {{ old('scope_type') === 'category' ? 'selected' : '' }}>Category</option>
            <option value="seller_type" {{ old('scope_type') === 'seller_type' ? 'selected' : '' }}>Seller Type</option>
            <option value="seller" {{ old('scope_type') === 'seller' ? 'selected' : '' }}>Specific Seller</option>
        </select>
    </div>

    <div id="scope_category" class="scope-extra" style="display:none;">
        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
        <select name="scope_id" class="scope-category-select w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
            <option value="">Select category</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('scope_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <div id="scope_seller_type" class="scope-extra" style="display:none;">
        <label class="block text-sm font-medium text-gray-700 mb-2">Seller Type</label>
        <select name="seller_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
            <option value="business" {{ old('seller_type') === 'business' ? 'selected' : '' }}>Business</option>
            <option value="private" {{ old('seller_type') === 'private' ? 'selected' : '' }}>Private</option>
        </select>
    </div>

    <div id="scope_seller" class="scope-extra" style="display:none;">
        <label class="block text-sm font-medium text-gray-700 mb-2">Seller</label>
        <select name="scope_id" class="scope-seller-select w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
            <option value="">Select seller</option>
            @foreach ($sellers as $s)
                <option value="{{ $s->id }}" {{ old('scope_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->email }})</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Commission Type</label>
        <select name="commission_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" required>
            <option value="percentage" {{ old('commission_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
            <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Value</label>
        <input type="number" name="value" value="{{ old('value', 5) }}" min="0" step="0.01" required
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
        <p class="text-xs text-gray-500 mt-1">Percentage (e.g. 5) or fixed amount</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
        <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
        <p class="text-xs text-gray-500 mt-1">Higher = more specific (applied first)</p>
    </div>

    <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Add Rule</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scopeType = document.getElementById('scope_type');
    const toggles = {
        scope_category: document.getElementById('scope_category'),
        scope_seller_type: document.getElementById('scope_seller_type'),
        scope_seller: document.getElementById('scope_seller')
    };
    function update() {
        Object.values(toggles).forEach(el => el.style.display = 'none');
        document.querySelectorAll('.scope-category-select, .scope-seller-select').forEach(s => { s.name = 'scope_id_off'; });
        const v = scopeType.value;
        if (v === 'category') {
            toggles.scope_category.style.display = 'block';
            document.querySelector('.scope-category-select').name = 'scope_id';
        } else if (v === 'seller_type') {
            toggles.scope_seller_type.style.display = 'block';
        } else if (v === 'seller') {
            toggles.scope_seller.style.display = 'block';
            document.querySelector('.scope-seller-select').name = 'scope_id';
        }
    }
    scopeType.addEventListener('change', update);
    update();
});
</script>
@endsection
