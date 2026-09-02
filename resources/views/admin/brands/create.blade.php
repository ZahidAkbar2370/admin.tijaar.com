@extends('admin.layouts.app')

@section('title', 'Add Brand')

@section('admin-content')
<div class="w-full min-w-0">
    {{-- Breadcrumb --}}
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-sm text-gray-500">
            <li><a href="{{ route('admin.brands.index') }}" class="hover:text-primary transition">Brands</a></li>
            <li><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></li>
            <li class="font-medium text-gray-900">Add Brand</li>
        </ol>
    </nav>

    {{-- Page header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add Brand</h1>
        <p class="mt-1 text-gray-500">Create a new brand for your catalog.</p>
    </div>

    @include('admin.brands._form')
</div>
@endsection
