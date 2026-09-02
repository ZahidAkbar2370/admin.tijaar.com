@extends('admin.layouts.app')

@section('title', $blog ? 'Edit Blog' : 'Create Blog')

@section('admin-content')
<div class="w-full min-w-0">
    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="mb-8">
        <a href="{{ route('admin.blogs.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Blogs
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $blog ? 'Edit Blog Post' : 'Create Blog Post' }}</h1>
        <p class="text-gray-500 mt-1">Write and publish blog posts. Use the editor for rich text, images, and links.</p>
    </div>

    <form method="POST" action="{{ $blog ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}" enctype="multipart/form-data" class="space-y-8" id="blog-form">
        @csrf
        @if ($blog) @method('PUT') @endif

        {{-- Post identity --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </span>
                    Post identity
                </h2>
                <p class="text-sm text-gray-500 mt-1">Title and URL slug. The slug is used in the blog post URL (e.g. /blog/your-slug).</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $blog?->title) }}" required
                               placeholder="e.g. 5 Tips for Selling on Tijaar"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $blog?->slug) }}"
                               placeholder="Leave empty to auto-generate from title"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Use lowercase letters, numbers, and hyphens only. Empty = generated from title.</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                    <textarea name="excerpt" rows="2"
                              placeholder="Short summary shown in blog listing and search results (1–2 sentences)."
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('excerpt', $blog?->excerpt) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    Content
                </h2>
                <p class="text-sm text-gray-500 mt-1">Write and format the post body. Use the toolbar for headings, bold, lists, links, and images. Click the image icon to upload a picture.</p>
            </div>
            <div class="p-6">
                <div class="rounded-xl border border-gray-200 overflow-hidden bg-white">
                    <div id="blog-content-editor" class="min-h-[320px] text-sm" style="font-family: Open Sans, sans-serif;">{!! old('content', $blog?->content) !!}</div>
                </div>
                <textarea name="content" id="blog-content-value" class="hidden" aria-hidden="true" tabindex="-1">{{ old('content', $blog?->content) }}</textarea>
            </div>
        </div>

        {{-- Featured image --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                    </span>
                    Featured image
                </h2>
                <p class="text-sm text-gray-500 mt-1">Main image shown in the blog listing and at the top of the post. Recommended: 1200×630 px.</p>
            </div>
            <div class="p-6 space-y-4">
                @if ($blog?->featured_image)
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-2">Current image</p>
                        <img src="{{ \App\Support\UploadHelper::url($blog->featured_image) }}" alt="" class="max-h-40 rounded-xl border border-gray-200 object-cover" />
                    </div>
                @endif
                <div id="blog-image-preview" class="hidden">
                    <p class="text-xs font-medium text-gray-500 mb-2">New image preview</p>
                    <img id="blog-image-preview-img" src="" alt="" class="w-48 h-32 object-cover rounded-xl border border-gray-200" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $blog?->featured_image ? 'Replace featured image' : 'Upload featured image' }}</label>
                    <input type="file" name="featured_image" id="blog-featured-image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium" />
                    <p class="text-xs text-gray-500 mt-1">Optional. Leave empty to keep the current image. Max 5 MB.</p>
                    @include('admin.partials.image-alt-field', ['name' => 'featured_image_alt', 'value' => old('featured_image_alt', $blog?->featured_image_alt)])
                </div>
                <script>
                    document.getElementById('blog-featured-image')?.addEventListener('change', function(e) {
                        var preview = document.getElementById('blog-image-preview');
                        var img = document.getElementById('blog-image-preview-img');
                        if (!preview || !img) return;
                        var file = e.target.files?.[0];
                        if (file && file.type.startsWith('image/')) {
                            img.src = URL.createObjectURL(file);
                            preview.classList.remove('hidden');
                        } else {
                            img.src = '';
                            preview.classList.add('hidden');
                        }
                    });
                </script>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    SEO
                </h2>
                <p class="text-sm text-gray-500 mt-1">Search engine title, description, keywords, and canonical URL for this post on the public site.</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $blog?->meta_title) }}"
                               placeholder="e.g. Online Shopping Return Policy Pakistan – Tijaar Blog"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Browser tab &amp; Google title. Leave empty to use post title.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $blog?->meta_keywords) }}"
                               placeholder="e.g. online shopping, returns, pakistan"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Comma-separated keywords (optional).</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta description</label>
                    <textarea name="meta_description" rows="3"
                              placeholder="Short summary for Google and social previews (around 150–160 characters)."
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('meta_description', $blog?->meta_description) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Shown in search results. Leave empty to auto-generate from excerpt or content.</p>
                </div>
                @php
                    $frontendBase = rtrim(config('app.frontend_url', 'https://tijaar.com'), '/');
                    $previewSlug = old('slug', $blog?->slug) ?: 'your-post-slug';
                @endphp
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 text-sm">
                    <p class="font-medium text-gray-800 mb-2">Canonical URL (auto)</p>
                    <p class="text-gray-600 break-all font-mono text-xs">{{ $frontendBase }}/blog/<span id="blog-canonical-slug">{{ $previewSlug }}</span></p>
                    <p class="text-xs text-gray-500 mt-2">Used as the canonical link for this post. Blog listing page canonical is <code class="bg-white px-1 rounded">{{ $frontendBase }}/blogs</code>.</p>
                </div>
            </div>
        </div>

        {{-- Publishing --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                    Publishing
                </h2>
                <p class="text-sm text-gray-500 mt-1">When this post goes live on the public site.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Published at</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $blog?->published_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Leave empty for &quot;now&quot; when publishing.</p>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="hidden" name="is_published" value="0" />
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $blog?->is_published) ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5" />
                            <span class="text-sm font-medium text-gray-700">Publish this post (visible to visitors). Uncheck to save as draft.</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm transition">Save post</button>
            <a href="{{ route('admin.blogs.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
        </div>
    </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var uploadUrl = '{{ route("admin.pages.upload-image") }}';
    var csrf = '{{ csrf_token() }}';
    var form = document.getElementById('blog-form');
    var valueEl = document.getElementById('blog-content-value');

    var quill = new Quill('#blog-content-editor', {
        theme: 'snow',
        placeholder: 'Write your post content here…',
        modules: {
            toolbar: {
                container: [
                    [{ 'header': [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['blockquote', 'code-block'],
                    ['clean']
                ],
                handlers: {
                    image: function() {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
                        input.onchange = function() {
                            var file = input.files && input.files[0];
                            if (!file) return;
                            var fd = new FormData();
                            fd.append('file', file);
                            fd.append('_token', csrf);
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', uploadUrl);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.setRequestHeader('Accept', 'application/json');
                            xhr.onload = function() {
                                if (xhr.status >= 200 && xhr.status < 300) {
                                    try {
                                        var j = JSON.parse(xhr.responseText);
                                        var url = j.location || j.url;
                                        if (url) {
                                            var range = quill.getSelection(true) || { index: quill.getLength() };
                                            quill.insertEmbed(range.index, 'image', url);
                                        }
                                    } catch (e) {}
                                }
                            };
                            xhr.send(fd);
                        };
                        input.click();
                    }
                }
            }
        }
    });

    function syncContent() {
        if (valueEl) valueEl.value = quill.root.innerHTML;
    }
    quill.on('text-change', syncContent);
    syncContent();

    if (form) form.addEventListener('submit', function() {
        syncContent();
    });

    var slugInput = document.querySelector('input[name="slug"]');
    var canonicalSlug = document.getElementById('blog-canonical-slug');
    if (slugInput && canonicalSlug) {
        slugInput.addEventListener('input', function() {
            canonicalSlug.textContent = slugInput.value.trim() || 'your-post-slug';
        });
    }
});
</script>
@endsection
