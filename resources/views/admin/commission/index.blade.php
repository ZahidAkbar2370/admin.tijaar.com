@extends('admin.layouts.app')

@section('title', 'Commission')

@section('admin-content')
<div class="w-full min-w-0">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Commission</h1>
    <p class="text-sm text-gray-500 mb-6">Business seller advanced rules. Default seller commission is under <a href="{{ route('admin.seller-settings.index') }}" class="text-primary hover:underline">Customer as Seller</a>; customer-seller order fees under <a href="{{ route('admin.people-settings.index', ['tab' => 'seller']) }}" class="text-primary hover:underline">Customer as Seller</a>.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="group block bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-primary/40 hover:shadow-md transition">
                <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary transition">{{ $item['label'] }}</h2>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $item['description'] }}</p>
                <p class="mt-4 text-sm font-medium text-primary">Configure →</p>
            </a>
        @endforeach
    </div>
</div>
@endsection
