@extends('admin.layouts.app')

@section('title', 'Brands')

@section('admin-content')
<div x-data="brandDrawer()">

@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Brands</h1>
        <p class="text-sm text-gray-500 mt-1">Manage product brands</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.brands.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
        </a>
        <button type="button" @click="openDrawer()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Brand
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.brands.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Brand name..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">Filter</button>
            @if (request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.brands.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
            @endif
            <div class="relative ml-auto">
                <button type="button" @click="showColumnMenu = !showColumnMenu" class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Columns
                </button>
                <div x-show="showColumnMenu" @click.away="showColumnMenu = false" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2" style="z-index: 9999;">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Toggle Columns</p>
                    <template x-for="(col, key) in columns" :key="key">
                        <label class="column-toggle-item flex items-center gap-3 px-4 py-2 cursor-pointer transition">
                            <input type="checkbox" x-model="col.visible" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                            <span class="text-sm text-gray-700" x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th x-show="columns.id.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th x-show="columns.name.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                    <th x-show="columns.website.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Website</th>
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.featured.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Featured</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($brands as $b)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $b->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden relative">
                                @if ($b->logo && $b->logo_url)
                                    <img src="{{ $b->logo_url }}" alt="" class="w-10 h-10 rounded-lg object-cover absolute inset-0" onerror="this.style.display='none'; this.nextElementSibling && this.nextElementSibling.classList.remove('hidden');" />
                                    <span class="hidden w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    </span>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $b->name }}</p>
                                <p class="text-xs text-gray-400">{{ $b->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td x-show="columns.website.visible" class="px-6 py-4 text-sm text-gray-500">
                        @if ($b->website)
                            <a href="{{ $b->website }}" target="_blank" class="text-primary hover:underline">{{ parse_url($b->website, PHP_URL_HOST) }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @if ($b->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                            </span>
                        @endif
                    </td>
                    <td x-show="columns.featured.visible" class="px-6 py-4">
                        @if ($b->is_featured)
                            <span class="text-amber-500">★</span>
                        @else
                            <span class="text-gray-300">☆</span>
                        @endif
                    </td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openDrawer({{ $b->id }})" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('admin.brands.destroy', $b) }}" class="inline" onsubmit="return sweetConfirm(event, 'Delete this brand? This cannot be undone.', 'Delete brand?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <p class="text-gray-500 font-medium">No brands found</p>
                        <button type="button" @click="openDrawer()" class="text-primary font-medium hover:underline mt-2">Create your first brand</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($brands->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $brands->links() }}
    </div>
    @endif
</div>

{{-- Drawer 40% width - teleport for smooth slide --}}
<template x-teleport="body">
<div x-show="open" x-cloak class="fixed inset-0 z-[9990]" aria-hidden="true">
    <div class="absolute inset-0" @click="closeDrawer()">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    </div>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-350" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-[40%] bg-white shadow-2xl flex flex-col" style="transform: translateZ(0);">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                        <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Edit Brand' : 'Add Brand'"></h2>
                        <button type="button" @click="closeDrawer()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit="submitForm($event)" class="flex-1 overflow-y-auto">
                        @csrf
                        <div class="space-y-5 p-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                <input type="text" name="slug" x-model="form.slug" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" name="website" x-model="form.website" placeholder="https://example.com" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" x-model="form.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm"></textarea>
                            </div>

                            {{-- Logo Dropzone --}}
                            <div x-data="dropzone()">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                                <div @dragover.prevent="drag=true" @dragleave.prevent="drag=false" @drop.prevent="handleDrop($event)"
                                     :class="{'dropzone-border': true, 'drag-over': drag}"
                                     class="rounded-xl p-6 text-center cursor-pointer"
                                     @click="$refs.fileInput.click()">
                                    <input type="file" x-ref="fileInput" name="logo" accept="image/*" @change="handleSelect($event)" class="hidden" />
                                    <template x-if="!preview">
                                        <div class="text-gray-500">
                                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <p class="text-sm">Drop logo or click to upload</p>
                                        </div>
                                    </template>
                                    <template x-if="preview">
                                        <img :src="preview" class="max-h-32 mx-auto rounded-lg" />
                                    </template>
                                </div>
                            </div>

                            <hr class="border-gray-100" />
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">SEO Meta</p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                <input type="text" name="meta_title" x-model="form.meta_title" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                <textarea name="meta_description" x-model="form.meta_description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                                <input type="text" name="meta_keywords" x-model="form.meta_keywords" placeholder="keyword1, keyword2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>

                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_featured" value="1" x-model="form.is_featured" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                                    <span class="text-sm text-gray-700">Featured</span>
                                </label>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 p-6 flex gap-3">
                            <button type="submit" :disabled="saving" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition disabled:opacity-50">
                                <span x-show="!saving">Save</span>
                                <span x-show="saving">Saving...</span>
                            </button>
                            <button type="button" @click="closeDrawer()" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</button>
                        </div>
                    </form>
    </div>
</div>
</template>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('brandDrawer', () => ({
        open: false,
        editingId: null,
        saving: false,
        errors: {},
        showColumnMenu: false,
        columns: {
            id: { label: 'ID', visible: true },
            name: { label: 'Name', visible: true },
            website: { label: 'Website', visible: true },
            status: { label: 'Status', visible: true },
            featured: { label: 'Featured', visible: true },
            actions: { label: 'Actions', visible: true }
        },
        form: { name: '', slug: '', description: '', website: '', is_active: true, is_featured: false, meta_title: '', meta_description: '', meta_keywords: '' },
        async openDrawer(id = null) {
            this.editingId = id;
            if (id) {
                try {
                    const r = await fetch('{{ url('admin/brands') }}/' + id + '/json');
                    const c = await r.json();
                    this.form = {
                        name: c.name || '',
                        slug: c.slug || '',
                        description: c.description || '',
                        website: c.website || '',
                        is_active: c.is_active !== false,
                        is_featured: c.is_featured || false,
                        meta_title: c.meta_title || '',
                        meta_description: c.meta_description || '',
                        meta_keywords: c.meta_keywords || ''
                    };
                } catch (_) {
                    this.form = { name: '', slug: '', description: '', website: '', is_active: true, is_featured: false, meta_title: '', meta_description: '', meta_keywords: '' };
                }
            } else {
                this.form = { name: '', slug: '', description: '', website: '', is_active: true, is_featured: false, meta_title: '', meta_description: '', meta_keywords: '' };
            }
            this.open = true;
        },
        closeDrawer() { this.open = false; },
        async submitForm(e) {
            e.preventDefault();
            this.saving = true;
            this.errors = {};
            const form = e.target;
            const fd = new FormData(form);
            if (this.editingId) fd.append('_method', 'PUT');
            const url = this.editingId ? '{{ url('admin/brands') }}/' + this.editingId : '{{ route('admin.brands.store') }}';
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await r.json();
                if (data.success) {
                    this.closeDrawer();
                    window.location.reload();
                } else {
                    this.errors = data.errors || {};
                }
            } catch (err) {
                this.errors = { general: 'Something went wrong.' };
            }
            this.saving = false;
        }
    }));

    Alpine.data('dropzone', () => ({
        drag: false,
        preview: null,
        handleDrop(e) { this.drag = false; const f = e.dataTransfer?.files?.[0]; if (f && f.type.startsWith('image/')) this.previewFile(f); },
        handleSelect(e) { const f = e.target.files?.[0]; if (f) this.previewFile(f); },
        previewFile(f) { const r = new FileReader(); r.onload = () => { this.preview = r.result; }; r.readAsDataURL(f); }
    }));
});
</script>
@endsection
