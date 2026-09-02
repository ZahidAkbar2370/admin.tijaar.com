@extends('admin.layouts.app')

@section('title', 'Edit: ' . ($page->title ?? 'Page'))

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: {{ $page->title }}</h1>
        <p class="text-gray-500 mt-1">Update the title, URL slug, body content, and SEO. Changes appear on the public site after saving.</p>
    </div>

@if ($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
        <p class="font-semibold mb-2">Please fix the following:</p>
        <ul class="list-disc list-inside space-y-1">{{ $errors->first() }}</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.pages.update', $page) }}" class="w-full min-w-0" id="page-edit-form">
    @csrf
    @method('PUT')

    {{-- Page identity --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
                Page identity
            </h2>
            <p class="text-sm text-gray-500 mt-1">Page title and address. The URL is fixed for each page.</p>
        </div>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required placeholder="e.g. About Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    <p class="text-xs text-gray-500 mt-1">Shown as the main heading and in the banner.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page address (fixed)</label>
                    <div class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-600 text-sm font-mono">{{ url($page->slug) }}</div>
                    <p class="text-xs text-gray-500 mt-1">This page is always available at the address above.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Page content --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                Page content
            </h2>
            <p class="text-sm text-gray-500 mt-1">Write and format the body. Use the toolbar for bold, headings, lists, links, and images. Click the image icon to upload a picture.</p>
            <p class="text-sm text-amber-700 mt-1 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"><strong>Tip:</strong> For a main heading (large title on the site), use the <strong>first dropdown</strong> in the toolbar and choose <strong>Heading 1</strong> — do not only use bold.</p>
        </div>
        <div class="p-4 sm:p-6">
        <div class="rounded-xl border border-gray-200 overflow-hidden bg-white shadow-inner">
            <input type="hidden" name="content" id="page-content-field" value="">
            <div id="quill-editor" class="min-h-[320px] text-sm" style="min-height: 380px;">{!! old('content', $page->content) !!}</div>
        </div>
        </div>
    </div>

    {{-- Page banner (title + text only) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </span>
                Page banner
            </h2>
            <p class="text-sm text-gray-500 mt-1">Title and short text shown in the blue banner at the top of this page (no image).</p>
        </div>
        <div class="p-4 sm:p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Banner title</label>
                <input type="text" name="banner_title" value="{{ old('banner_title', $page->banner_title) }}" placeholder="e.g. Get in Touch" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                <p class="text-xs text-gray-500 mt-1">Leave empty to use the page title.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Banner text</label>
                <input type="text" name="banner_subtitle" value="{{ old('banner_subtitle', $page->banner_subtitle) }}" placeholder="e.g. Have a question? We're here to help." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                SEO (search engines)
            </h2>
            <p class="text-sm text-gray-500 mt-1">Optional. These help search engines show your page correctly in results.</p>
        </div>
        <div class="p-4 sm:p-6 space-y-4">
            @include('admin.pages.partials.seo-fields', ['page' => $page])
        </div>
    </div>

    {{-- Visibility & order --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </span>
                Visibility &amp; order
            </h2>
            <p class="text-sm text-gray-500 mt-1">Control whether this page is visible on the site and its sort order in lists (if used).</p>
        </div>
        <div class="p-4 sm:p-6">
        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                <span class="text-sm font-medium text-gray-700">Page is live (visible to visitors)</span>
            </label>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0" class="w-24 px-3 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
            </div>
        </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl shadow-sm transition">
            Save changes
        </button>
        <a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        @if ($page->slug)
        <a href="{{ url($page->slug) }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View page on site →</a>
        @endif
    </div>
</form>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var uploadUrl = '{{ route("admin.pages.upload-image") }}';
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '{{ csrf_token() }}';
    var editorEl = document.getElementById('quill-editor');
    var fieldEl = document.getElementById('page-content-field');
    var initialHtml = editorEl.innerHTML.trim() || '<p><br></p>';
    editorEl.innerHTML = '';
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write your page content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['link', 'image'],
                    ['blockquote', 'code-block'],
                [{ 'align': [] }],
                    ['clean']
            ]
        }
    });
    quill.root.innerHTML = initialHtml;
    quill.getModule('toolbar').addHandler('image', function() {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
                        input.onchange = function() {
                            var file = input.files && input.files[0];
                            if (!file) return;
                            var fd = new FormData();
                            fd.append('file', file);
            fd.append('_token', csrfToken);
            fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var url = data.location || data.url || '';
                    if (url) quill.insertEmbed(quill.getSelection(true).index, 'image', url);
                })
                .catch(function() {});
                        };
                        input.click();
    });
    var form = document.getElementById('page-edit-form');
    if (form) {
        form.addEventListener('submit', function() {
            fieldEl.value = quill.root.innerHTML;
        });
    }
});
</script>
</div>
@endsection
