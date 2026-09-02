@extends('admin.layouts.app')

@section('title', 'Edit Expense')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.expenses.show', $expense) }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Expense
    </a>
</div>

<div class="w-full min-w-0 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Expense</h1>

    <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf
        @method('PUT')
        @include('admin.expenses._form', ['categories' => $categories, 'expense' => $expense])

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Update Expense</button>
            <a href="{{ route('admin.expenses.show', $expense) }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
