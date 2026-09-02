<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Meta title</label>
    <input type="text" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}"
           placeholder="Title shown in browser tab and search results"
           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
    <p class="text-xs text-gray-500 mt-1">Used for SEO &lt;title&gt; on the public site.</p>
</div>
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Meta description</label>
    <textarea name="meta_description" rows="2"
              placeholder="Short summary for Google and social previews"
              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('meta_description', $page->meta_description) }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Meta keywords</label>
    <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}"
           placeholder="e.g. marketplace, shop, tijaar"
           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
</div>
