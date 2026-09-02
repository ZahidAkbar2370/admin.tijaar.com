@php
    $isEdit = isset($brand);
    $brand = $brand ?? null;
@endphp
<form method="POST" action="{{ $isEdit ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    {{-- Basic info --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-900">Basic information</h2>
            </div>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name', $brand?->name) }}" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition placeholder-gray-400"
                    placeholder="Brand name" />
                @error('name') <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1.5">Slug <span class="text-gray-400 font-normal">(auto-generated if empty)</span></label>
                <input id="slug" type="text" name="slug" value="{{ old('slug', $brand?->slug) }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition placeholder-gray-400"
                    placeholder="brand-slug" />
                @error('slug') <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1.5">Website</label>
                <input id="website" type="url" name="website" value="{{ old('website', $brand?->website) }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition placeholder-gray-400"
                    placeholder="https://example.com" />
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition placeholder-gray-400 resize-none"
                    placeholder="Short description of the brand">{{ old('description', $brand?->description) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Logo --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-900">Logo</h2>
            </div>
        </div>
        <div class="p-6">
            @if ($brand?->logo)
            <div class="flex flex-wrap items-end gap-4 mb-4">
                <div class="w-24 h-24 rounded-xl border border-gray-200 overflow-hidden bg-gray-50 flex-shrink-0">
                    <img src="{{ \App\Support\UploadHelper::url($brand->logo) }}" alt="" class="w-full h-full object-contain" />
                </div>
                <p class="text-sm text-gray-500">Current logo. Upload a new file to replace.</p>
            </div>
            @endif
            <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-primary/30 hover:bg-primary/5 transition-colors">
                <input type="file" name="logo" accept="image/*" id="logo"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer cursor-pointer" />
                <p class="text-xs text-gray-400 mt-2">PNG, JPG or WebP. Recommended size 200×200px.</p>
            </div>
            @include('admin.partials.image-alt-field', ['name' => 'logo_alt', 'value' => old('logo_alt', $brand?->logo_alt)])
        </div>
    </div>

    {{-- Status --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="font-semibold text-gray-900">Visibility</h2>
            </div>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 mb-4">Only <strong>Active</strong> and <strong>Featured</strong> brands appear in the &quot;Trusted by Top Brands&quot; section on the home page.</p>
            <div class="flex flex-wrap gap-8">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $brand?->is_active ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0" />
                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Active</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="hidden" name="is_featured" value="0" />
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $brand?->is_featured) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/20 focus:ring-offset-0" />
                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Featured on homepage</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3 pt-2">
        <button type="submit"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl shadow-sm hover:shadow transition-all duration-200">
            @if ($isEdit)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Update Brand
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8v8H4V4"/></svg>
                Create Brand
            @endif
        </button>
        <a href="{{ route('admin.brands.index') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Cancel
        </a>
    </div>
</form>
