@extends('admin.layouts.app')

@section('title', 'Payment Method')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Payment Method</h1>
    <p class="text-sm text-gray-500 mb-6">Choose a payment method to configure credentials and enable or disable it at checkout.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="group block bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-primary/40 hover:shadow-md transition">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-11 rounded-xl border border-gray-200 bg-white flex items-center justify-center overflow-hidden shrink-0">
                        <img src="{{ $item['logo_url'] }}" alt="{{ $item['label'] }}" class="max-w-full max-h-full object-contain p-1.5">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary transition">{{ $item['label'] }}</h2>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $item['description'] }}</p>
                            </div>
                            <span class="flex-shrink-0 inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold {{ $item['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $item['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm font-medium text-primary">Configure →</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
