@extends('admin.layouts.app')

@section('title', 'Abuse & Safety')

@section('admin-content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Abuse & Safety</h1>
    <p class="text-sm text-gray-500 mt-1">Configure auto-ban, max price, and blocked categories</p>
</div>

@if (session('success'))
    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="w-full min-w-0 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
    <h3 class="font-bold text-gray-900 mb-6">Settings</h3>
    <form method="POST" action="{{ route('admin.abuse-safety.update') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Auto-ban threshold (abuse score)</label>
                <input type="number" name="auto_ban_threshold" value="{{ $settings['auto_ban_threshold'] }}" min="0" max="1000" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                <p class="text-xs text-gray-400 mt-1">User is auto-banned when abuse_score reaches this value</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Max price (private listings)</label>
                <input type="number" name="max_price_private" value="{{ $settings['max_price_private'] }}" min="0" step="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Blocked categories (private) – comma-separated IDs</label>
                <input type="text" name="blocked_categories_private" value="{{ $settings['blocked_categories_private'] }}" placeholder="e.g. 1,5,12" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-800">Listing expiry</p>
                <p class="text-xs text-gray-500 mt-1">
                    Currently <span class="font-semibold text-gray-900">{{ $settings['listing_expiry_days'] }} days</span>.
                    Edit under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline">Customer as Seller → Product / item listing expiry</a>.
                </p>
            </div>
            <div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="duplicate_image_detection" value="0" />
                    <input type="checkbox" name="duplicate_image_detection" value="1" {{ $settings['duplicate_image_detection'] == '1' ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary" />
                    <span class="text-sm font-medium text-gray-700">Duplicate image detection (future AI)</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">Placeholder for future AI-based duplicate detection</p>
            </div>
        </div>
        <div class="mt-6">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Save settings</button>
        </div>
    </form>
</div>

<div class="mt-6">
    <a href="{{ route('admin.abuse-safety.flagged') }}" class="inline-flex items-center gap-2 text-primary hover:underline text-sm font-medium">View flagged items →</a>
</div>
@endsection
