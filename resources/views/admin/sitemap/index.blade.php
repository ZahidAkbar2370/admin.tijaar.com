@extends('admin.layouts.app')

@section('title', 'Sitemaps')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Sitemaps</h1>
    <p class="text-sm text-gray-500 mt-1">Manage XML sitemaps served on the public site. Main index: <a href="{{ $frontendBase }}/sitemap.xml" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $frontendBase }}/sitemap.xml</a></p>
</div>

<div class="grid gap-6 lg:grid-cols-3 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 uppercase">Static URLs</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['static_count'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 uppercase">Categories (auto)</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['category_count'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 uppercase">Products (auto)</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['product_count'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $stats['product_files'] }} file(s) @ 5000 max each</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Main sitemap index</h2>
    <p class="text-sm text-gray-600 mb-4">Child sitemaps listed in <code class="text-xs bg-gray-100 px-1 rounded">/sitemap.xml</code></p>
    <ul class="space-y-2 text-sm">
        @forelse ($indexEntries as $entry)
            <li>
                <a href="{{ $entry['loc'] }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ $entry['loc'] }}</a>
                <span class="text-gray-400 ml-2">lastmod {{ $entry['lastmod'] }}</span>
            </li>
        @empty
            <li class="text-gray-500">No child sitemaps enabled.</li>
        @endforelse
    </ul>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Sitemap toggles &amp; defaults</h2>
    <form method="POST" action="{{ url('/admin/sitemap/config') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="enable_static" value="0" />
                <input type="checkbox" name="enable_static" value="1" {{ $config['enable_static'] ? 'checked' : '' }} class="rounded border-gray-300 text-primary" />
                <span class="text-sm text-gray-700">Include static sitemap</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="enable_categories" value="0" />
                <input type="checkbox" name="enable_categories" value="1" {{ $config['enable_categories'] ? 'checked' : '' }} class="rounded border-gray-300 text-primary" />
                <span class="text-sm text-gray-700">Include categories (auto)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="enable_products" value="0" />
                <input type="checkbox" name="enable_products" value="1" {{ $config['enable_products'] ? 'checked' : '' }} class="rounded border-gray-300 text-primary" />
                <span class="text-sm text-gray-700">Include products (auto)</span>
            </label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max products per file</label>
                <input type="number" name="products_per_file" min="100" max="5000" value="{{ $config['products_per_file'] }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product changefreq</label>
                <select name="product_changefreq" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                    @foreach (['daily', 'weekly', 'monthly'] as $freq)
                        <option value="{{ $freq }}" {{ $config['product_changefreq'] === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product priority</label>
                <input type="number" step="0.1" min="0" max="1" name="product_priority" value="{{ $config['product_priority'] }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category changefreq</label>
                <select name="category_changefreq" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                    @foreach (['daily', 'weekly', 'monthly'] as $freq)
                        <option value="{{ $freq }}" {{ $config['category_changefreq'] === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category priority</label>
                <input type="number" step="0.1" min="0" max="1" name="category_priority" value="{{ $config['category_priority'] }}"
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm" />
            </div>
        </div>
        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Save settings</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-2">Manual XML editor</h2>
    <p class="text-sm text-gray-600 mb-6">Har sitemap file ko <strong>Auto</strong> (database/settings se) ya <strong>Manual</strong> (apna XML) par set karein. Manual mode ON hone par wahi XML live site par serve hogi.</p>

    <div class="space-y-4">
        @foreach ($sitemapFiles as $file)
            <details class="border border-gray-200 rounded-xl overflow-hidden group" {{ $file['mode'] === 'manual' ? 'open' : '' }}>
                <summary class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-gray-50 cursor-pointer list-none">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="font-medium text-gray-900">{{ $file['label'] }}</span>
                        @if ($file['mode'] === 'manual')
                            <span class="text-xs font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Manual</span>
                        @else
                            <span class="text-xs font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Auto</span>
                        @endif
                    </div>
                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="text-sm text-primary hover:underline shrink-0" onclick="event.stopPropagation()">View live</a>
                </summary>
                <div class="p-4 border-t border-gray-200">
                    <form method="POST" action="{{ url('/admin/sitemap/overrides') }}" class="space-y-4" x-data="{ mode: '{{ $file['mode'] }}' }">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="file_key" value="{{ $file['key'] }}" />
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="auto" x-model="mode" class="text-primary" />
                                <span class="text-sm text-gray-700">Auto generate</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="manual" x-model="mode" class="text-primary" />
                                <span class="text-sm text-gray-700">Manual XML</span>
                            </label>
                        </div>
                        <div x-show="mode === 'manual'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">XML content</label>
                            <textarea name="manual_xml" rows="14" spellcheck="false"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                      placeholder="Paste or edit full sitemap XML here">{{ old('manual_xml', $file['manual_xml'] ?: $file['auto_xml']) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Index file: <code>&lt;sitemapindex&gt;</code> · Baqi files: <code>&lt;urlset&gt;</code></p>
                        </div>
                        <div x-show="mode === 'auto'" x-cloak class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Current auto preview</p>
                            <pre class="text-xs font-mono text-gray-700 whitespace-pre-wrap max-h-48 overflow-auto">{{ $file['auto_xml'] }}</pre>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Save file</button>
                        </div>
                    </form>
                </div>
            </details>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Static URLs</h2>
            <p class="text-sm text-gray-500 mt-1">Served at <a href="{{ $frontendBase }}/sitemap-static.xml" target="_blank" rel="noopener" class="text-primary hover:underline">/sitemap-static.xml</a></p>
        </div>
    </div>
    <div class="divide-y divide-gray-100">
        @foreach ($staticUrls as $row)
            <div class="p-4 grid gap-3 lg:grid-cols-12 lg:items-end">
                <form method="POST" action="{{ url('/admin/sitemap/static/' . $row->id) }}" class="contents">
                    @csrf
                    @method('PUT')
                    <div class="lg:col-span-3">
                        <label class="block text-xs text-gray-500 mb-1">Path</label>
                        <input type="text" name="path" value="{{ $row->path }}" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Changefreq</label>
                        <select name="changefreq" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            @foreach (['daily', 'weekly', 'monthly'] as $freq)
                                <option value="{{ $freq }}" {{ $row->changefreq === $freq ? 'selected' : '' }}>{{ $freq }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Priority</label>
                        <input type="number" step="0.1" min="0" max="1" name="priority" value="{{ $row->priority }}" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Order</label>
                        <input type="number" name="sort_order" value="{{ $row->sort_order }}" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
                    </div>
                    <div class="lg:col-span-2 flex items-center gap-2 pt-5">
                        <input type="hidden" name="is_enabled" value="0" />
                        <input type="checkbox" name="is_enabled" value="1" {{ $row->is_enabled ? 'checked' : '' }} class="rounded border-gray-300 text-primary" />
                        <span class="text-sm text-gray-600">Enabled</span>
                    </div>
                    <div class="lg:col-span-3">
                        <button type="submit" class="px-4 py-2 bg-primary text-white text-sm rounded-lg">Update</button>
                    </div>
                </form>
                <div class="lg:col-span-12 lg:col-start-10 lg:-mt-12 flex justify-end">
                    <form method="POST" action="{{ url('/admin/sitemap/static/' . $row->id) }}" onsubmit="return confirm('Remove this URL?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-red-600 border border-red-200 text-sm rounded-lg">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    <div class="p-6 border-t border-gray-100 bg-gray-50/50">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Add static URL</h3>
        <form method="POST" action="{{ url('/admin/sitemap/static') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Path</label>
                <input type="text" name="path" placeholder="/about" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Changefreq</label>
                <select name="changefreq" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option value="daily">daily</option>
                    <option value="weekly">weekly</option>
                    <option value="monthly" selected>monthly</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Priority</label>
                <input type="number" step="0.1" min="0" max="1" name="priority" value="0.5" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div class="flex items-center gap-2 pt-5">
                <input type="hidden" name="is_enabled" value="0" />
                <input type="checkbox" name="is_enabled" value="1" checked class="rounded border-gray-300 text-primary" />
                <span class="text-sm text-gray-600">Enabled</span>
            </div>
            <div>
                <button type="submit" class="px-4 py-2.5 bg-primary text-white text-sm rounded-xl w-full sm:w-auto">Add URL</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-2">Auto sitemaps</h2>
    <ul class="text-sm text-gray-600 space-y-2">
        <li><strong>Categories:</strong> <a href="{{ $frontendBase }}/sitemap-categories.xml" target="_blank" rel="noopener" class="text-primary hover:underline">/sitemap-categories.xml</a> — all active categories</li>
        <li><strong>Products:</strong>
            @if ($stats['product_files'] > 0)
                @for ($i = 1; $i <= $stats['product_files']; $i++)
                    <a href="{{ $frontendBase }}/sitemap-products-{{ $i }}.xml" target="_blank" rel="noopener" class="text-primary hover:underline">/sitemap-products-{{ $i }}.xml</a>@if ($i < $stats['product_files']), @endif
                @endfor
            @else
                <span class="text-gray-500">No published products yet</span>
            @endif
        </li>
    </ul>
</div>
@endsection
