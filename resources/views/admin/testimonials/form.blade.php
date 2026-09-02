@extends('admin.layouts.app')

@section('title', $testimonial ? 'Edit Testimonial' : 'Add Testimonial')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.testimonials.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Testimonials
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $testimonial ? 'Edit Testimonial' : 'Add Testimonial' }}</h1>
        <p class="text-gray-500 mt-1">Testimonials appear on the homepage and build trust with visitors.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $testimonial ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @if ($testimonial) @method('PUT') @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Testimonial details</h2>
                <p class="text-sm text-gray-500 mt-1">Name, role, and quote shown to visitors.</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $testimonial?->name) }}" required placeholder="e.g. Sarah Ahmed"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role / Title</label>
                        <input type="text" name="role" value="{{ old('role', $testimonial?->role) }}" placeholder="e.g. Shop Owner"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company (optional)</label>
                        <input type="text" name="company" value="{{ old('company', $testimonial?->company) }}" placeholder="e.g. Fashion Hub"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quote / Testimonial <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="4" required placeholder="What did they say about your platform?"
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('content', $testimonial?->content) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Keep it concise and authentic. Max 2000 characters.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating (1–5)</label>
                        <select name="rating" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">No rating</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('rating', $testimonial?->rating) == $i ? 'selected' : '' }}>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $testimonial?->sort_order ?? 0) }}" min="0"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo (optional)</label>
                    @if ($testimonial?->avatar)
                        <div class="mb-3">
                            <p class="text-xs text-gray-500 mb-2">Current photo</p>
                            <img src="{{ \App\Support\UploadHelper::url($testimonial->avatar) }}" alt="" class="w-16 h-16 rounded-full object-cover border border-gray-200" />
                        </div>
                    @endif
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium" />
                    <p class="text-xs text-gray-500 mt-1">Square image works best. Max 2 MB.</p>
                    @include('admin.partials.image-alt-field', ['name' => 'avatar_alt', 'value' => old('avatar_alt', $testimonial?->avatar_alt)])
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $testimonial?->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary/20" />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Show on site</label>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm transition">Save testimonial</button>
            <a href="{{ route('admin.testimonials.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
