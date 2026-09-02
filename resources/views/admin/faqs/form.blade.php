@extends('admin.layouts.app')

@section('title', $faq ? 'Edit FAQ' : 'Create FAQ')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.faqs.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to FAQs
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $faq ? 'Edit FAQ' : 'Create FAQ' }}</h1>
        <p class="text-gray-500 mt-1">Add or edit a frequently asked question. FAQs appear on the help or FAQ page for visitors.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $faq ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="space-y-8">
        @csrf
        @if ($faq) @method('PUT') @endif

        {{-- Question & answer --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    Question & answer
                </h2>
                <p class="text-sm text-gray-500 mt-1">The question visitors see and the full answer shown when they expand it.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Question <span class="text-red-500">*</span></label>
                        <input type="text" name="question" value="{{ old('question', $faq?->question) }}" required
                               placeholder="e.g. How do I track my order?"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Keep it clear and concise. This is the heading shown in the FAQ list.</p>
                        @error('question')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Answer <span class="text-red-500">*</span></label>
                        <textarea name="answer" rows="6" required
                                  placeholder="e.g. You can track your order from your account dashboard under My Orders. Click on the order to see tracking information and delivery status."
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('answer', $faq?->answer) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Full answer shown when the visitor expands this question. You can use multiple paragraphs.</p>
                        @error('answer')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Category & display --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </span>
                    Category & display
                </h2>
                <p class="text-sm text-gray-500 mt-1">Group FAQs by category (e.g. Orders, Payments, Returns) and control visibility and order.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" value="{{ old('category', $faq?->category) }}"
                               placeholder="e.g. Orders, Shipping, Returns, Payments"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Optional. FAQs with the same category can be grouped together on the public FAQ page.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $faq?->sort_order ?? 0) }}" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first within the same category.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faq?->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5" />
                            <span class="text-sm font-medium text-gray-700">FAQ is active (visible to visitors)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm transition">Save FAQ</button>
            <a href="{{ route('admin.faqs.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
