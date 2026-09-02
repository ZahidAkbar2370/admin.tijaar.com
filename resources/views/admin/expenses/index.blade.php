@extends('admin.layouts.app')

@section('title', 'Expenses')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tijaar Expenses</h1>
        <p class="text-sm text-gray-500 mt-1">Record and track platform operating expenses</p>
    </div>
    <a href="{{ route('admin.expenses.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Expense
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Filtered total</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">Rs {{ number_format($totalFiltered, 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Records shown</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $expenses->total() }}</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.expenses.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or description..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div class="w-48">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Category</label>
                <select name="category" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
            @if (request()->hasAny(['search', 'category', 'date_from', 'date_to']))
                <a href="{{ route('admin.expenses.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Proof</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($expenses as $expense)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.expenses.show', $expense) }}" class="font-medium text-gray-900 hover:text-primary">{{ $expense->title }}</a>
                        @if ($expense->description)
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $expense->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $expense->category_label }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rs {{ number_format((float) $expense->amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $expense->expense_date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if ($expense->proof_image)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Yes</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <a href="{{ route('admin.expenses.show', $expense) }}" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">View</a>
                        <a href="{{ route('admin.expenses.edit', $expense) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition">Edit</a>
                        <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" class="inline" onsubmit="return sweetConfirm(event, 'Delete this expense record?', 'Delete expense?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-gray-500">No expenses recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($expenses->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $expenses->links() }}</div>
    @endif
</div>
@endsection
