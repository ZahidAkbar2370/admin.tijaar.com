@extends('admin.layouts.app')

@section('title', 'Edit: Privacy Policy')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: Privacy Policy</h1>
        <p class="text-gray-500 mt-1">Update the Privacy Policy page: hero, last updated date, numbered sections (table below), and footer. Use the table to add, edit, delete, or reorder sections. Content uses a rich text editor.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">{{ $errors->first() }}</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" id="privacy-form" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Page title & hero --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Page identity &amp; hero</h2>
                <p class="text-sm text-gray-500 mt-1">Title and the blue header at the top. "Last updated" appears under the title.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required placeholder="Privacy Policy" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero title</label>
                    <input type="text" name="banner_title" value="{{ old('banner_title', $page->banner_title ?? 'Privacy Policy') }}" placeholder="Privacy Policy" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero description (subtitle)</label>
                    <input type="text" name="banner_subtitle" value="{{ old('banner_subtitle', $page->banner_subtitle ?? 'Please read this privacy policy carefully to understand how we collect, use, and protect your personal data.') }}" placeholder="Short line under the hero title" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last updated (shown under title)</label>
                    <input type="text" name="last_updated" value="{{ old('last_updated', $sections['last_updated'] ?? 'October 10, 2023') }}" placeholder="October 10, 2023" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
            </div>
        </div>

        @include('admin.pages.partials.sections-table', [
            'page' => $page,
            'sections' => $sections,
            'prefix' => 'privacy',
            'sectionsTitle' => 'Privacy Policy sections',
        ])

        {{-- Footer --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Footer</h2>
                <p class="text-sm text-gray-500 mt-1">Contact line and copyright at the bottom of the page.</p>
            </div>
            <div class="p-6 space-y-4">
                @php $footerContact = $sections['footer_contact_text'] ?? ''; $footerCopyright = $sections['footer_copyright'] ?? ''; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact text</label>
                    <input type="text" name="footer_contact_text" value="{{ old('footer_contact_text', $footerContact) }}" placeholder="Questions about the Privacy Policy? If you have any questions about this Privacy Policy, please contact us." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Copyright line</label>
                    <input type="text" name="footer_copyright" value="{{ old('footer_copyright', $footerCopyright) }}" placeholder="© 2024 Tijaar. All rights reserved." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
            </div>
        </div>

        {{-- SEO & visibility --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">SEO &amp; visibility</h2>
            </div>
            <div class="p-6 space-y-4">
                @include('admin.pages.partials.seo-fields', ['page' => $page])
                <div class="flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                        <span class="text-sm font-medium text-gray-700">Page is live</span>
                    </label>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0" class="w-24 px-3 py-2 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl shadow-sm transition">
                Save changes
            </button>
            <a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
            <a href="{{ url('privacy') }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View Privacy Policy page on site →</a>
        </div>
    </form>
</div>

@include('admin.pages.partials.section-modal', [
    'page' => $page,
    'prefix' => 'privacy',
])
@endsection
