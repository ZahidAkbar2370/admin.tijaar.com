@extends('admin.layouts.app')

@section('title', $expense->title)

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium w-fit">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Expenses
    </a>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.expenses.edit', $expense) }}" class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</a>
        <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" onsubmit="return sweetConfirm(event, 'Delete this expense record?', 'Delete expense?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-xl border border-red-200 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $expense->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">Recorded {{ $expense->created_at?->format('M j, Y g:i A') }}</p>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase">Category</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $expense->category_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase">Amount</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">Rs {{ number_format((float) $expense->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase">Expense date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $expense->expense_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase">Added by</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $expense->creator?->name ?? '—' }}</dd>
                </div>
            </dl>

            @if ($expense->description)
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase mb-2">Description</h2>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $expense->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-sm font-bold text-gray-900 mb-4">Proof image</h2>
            @if ($expense->proof_image)
                <a href="{{ $expense->proof_image_url }}" target="_blank" rel="noopener noreferrer" class="block group">
                    <img src="{{ $expense->proof_image_url }}" alt="Expense proof for {{ $expense->title }}" class="w-full rounded-xl border border-gray-200 object-cover max-h-80 group-hover:opacity-95 transition" />
                    <span class="inline-block mt-3 text-sm text-primary hover:underline">Open full image</span>
                </a>
            @else
                <p class="text-sm text-gray-500">No proof image uploaded.</p>
            @endif
        </div>
    </div>
</div>
@endsection
