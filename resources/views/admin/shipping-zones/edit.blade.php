@extends('admin.layouts.app')

@section('title', 'Edit Shipping Zone')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-6">
    <a href="{{ route('admin.shipping-zones.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Zones
    </a>
</div>

<div class="w-full min-w-0 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Zone: {{ $shippingZone->name }}</h2>
        <form method="POST" action="{{ route('admin.shipping-zones.update', $shippingZone) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name', $shippingZone->name) }}" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Market</label>
                    <select name="market" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                        <option value="PK" {{ old('market', $shippingZone->market) === 'PK' ? 'selected' : '' }}>PK</option>
                        <option value="AE" {{ old('market', $shippingZone->market) === 'AE' ? 'selected' : '' }}>AE</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $shippingZone->is_active) ? 'checked' : '' }} class="rounded" />
                    <span class="text-sm">Active</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium">Update Zone</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Shipping Rules</h3>

        <div class="space-y-3 mb-6">
            @foreach ($shippingZone->allRules as $r)
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                <div>
                    <p class="font-medium text-gray-900">{{ $r->name ?: ucfirst(str_replace('_', ' ', $r->type)) }}</p>
                    <p class="text-sm text-gray-500">{{ $r->type }} — {{ number_format($r->rate, 2) }} @if($r->free_threshold) | Free over {{ number_format($r->free_threshold, 2) }} @endif</p>
                </div>
                <form method="POST" action="{{ route('admin.shipping-zones.rules.destroy', [$shippingZone, $r]) }}" class="inline" onsubmit="return sweetConfirm(event, 'Remove this shipping rule?', 'Remove rule?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                </form>
            </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('admin.shipping-zones.rules.store', $shippingZone) }}" class="space-y-4 border-t pt-4">
            @csrf
            <h4 class="font-medium text-gray-700">Add Rule</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                    <input type="text" name="name" placeholder="Standard Delivery" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select name="type" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                        <option value="flat">Flat rate</option>
                        <option value="price_based">Price based</option>
                        <option value="weight_based">Weight based</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Rate *</label>
                    <input type="number" name="rate" step="0.01" min="0" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Free threshold</label>
                    <input type="number" name="free_threshold" step="0.01" min="0" placeholder="Free over X" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium">Add Rule</button>
        </form>
    </div>
</div>
@endsection
