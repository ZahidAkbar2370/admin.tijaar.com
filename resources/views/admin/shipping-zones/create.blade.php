@extends('admin.layouts.app')

@section('title', 'Add Shipping Zone')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.shipping-zones.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Zones
    </a>
</div>

<div class="w-full min-w-0">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Add Shipping Zone</h1>

    <form method="POST" action="{{ route('admin.shipping-zones.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Market *</label>
            <select name="market" class="w-full px-4 py-3 border border-gray-200 rounded-xl">
                <option value="PK" {{ old('market', 'PK') === 'PK' ? 'selected' : '' }}>PK (Pakistan)</option>
                <option value="AE" {{ old('market') === 'AE' ? 'selected' : '' }}>AE (UAE)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
            <input type="text" name="country" value="{{ old('country') }}" placeholder="Pakistan, UAE..." class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl" />
        </div>
        <div class="pt-4 flex gap-3">
            <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition">Create Zone</button>
            <a href="{{ route('admin.shipping-zones.index') }}" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
