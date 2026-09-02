@extends('admin.layouts.app')

@section('title', 'Categories')

@php
    $lucideIcons = config('lucide_icons', []);
@endphp

@section('admin-content')
<div x-data="categoryDrawer()" x-init="icons = @js($lucideIcons)">

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
        <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
        <p class="text-sm text-gray-500 mt-1">Manage product categories</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.categories.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
        </a>
        <button type="button" @click="openDrawer()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Category
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.categories.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Category name..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
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
                <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
            @endif
            <div class="relative ml-auto">
                <button type="button" @click="showColumnMenu = !showColumnMenu" class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Columns
                </button>
                <div x-show="showColumnMenu" @click.away="showColumnMenu = false" x-cloak x-transition class="column-toggle-dropdown absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2"
                     style="z-index: 9999;">
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
                    <th x-show="columns.parent.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Parent</th>
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.featured.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Featured</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $c)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $c->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if ($c->image)
                                <img src="{{ $c->image_url ?? (str_starts_with($c->image ?? '', 'upload/') ? asset($c->image) : asset('storage/' . $c->image)) }}" alt="" class="w-10 h-10 rounded-lg object-cover category-table-img" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center text-gray-400 text-xs category-table-placeholder" style="display: none;">No image</div>
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center text-gray-400 text-xs">No image</div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-900">{{ $c->name }}</p>
                                <p class="text-xs text-gray-400">{{ $c->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td x-show="columns.parent.visible" class="px-6 py-4 text-sm text-gray-500">{{ $c->parent?->name ?? '—' }}</td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @if ($c->is_active)
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
                        <button type="button" @click="toggleFeatured({{ $c->id }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/30 {{ $c->is_featured ? 'bg-primary' : 'bg-gray-200' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $c->is_featured ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </button>
                    </td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openDrawer({{ $c->id }})" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="inline" onsubmit="return sweetConfirm(event, 'Delete this category? This cannot be undone.', 'Delete category?')">
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
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                        <p class="text-gray-500 font-medium">No categories found</p>
                        <button type="button" @click="openDrawer()" class="text-primary font-medium hover:underline mt-2">Create your first category</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($categories->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $categories->links() }}
    </div>
    @endif
</div>

{{-- Drawer 40% width - teleport to body for smooth slide --}}
<template x-teleport="body">
<div x-show="open" x-cloak class="fixed inset-0 z-[9990]" aria-hidden="true">
    <div class="absolute inset-0" @click="closeDrawer()">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    </div>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-350"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-[40%] bg-white shadow-2xl flex flex-col"
         style="transform: translateZ(0);">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                        <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Edit Category' : 'Add Category'"></h2>
                        <button type="button" @click="closeDrawer()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit="submitForm($event)"
                          class="flex-1 overflow-y-auto"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-5 p-6">
                            <div x-show="errors.general" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" x-text="errors.general"></div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                                <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                <input type="text" name="slug" x-model="form.slug" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                                <input type="text" x-model="parentSearch" @input="filterParentOptions()" placeholder="Search parent category..." class="w-full px-4 py-2.5 mb-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm" />
                                <select name="parent_id" x-model="form.parent_id" x-ref="parentSelect" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                                    <option value="">— None (Root) —</option>
                                    @foreach ($parentCategories as $p)
                                    <option value="{{ $p->id }}" data-name="{{ $p->name }}" {{-- disabled when editing self handled in openDrawer --}}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1" x-show="parentSearch">Type to filter the list above; then pick from the dropdown.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" x-model="form.description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm"></textarea>
                            </div>

                            {{-- Image: show existing when editing, then dropzone for new --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                                <div x-show="editingId && form.existingImageUrl" class="mb-3">
                                    <img :src="form.existingImageUrl" alt="Current" class="max-h-32 rounded-lg border border-gray-200" />
                                    <p class="text-xs text-gray-500 mt-1">Current image. Choose a new file below to replace.</p>
                                </div>
                                <div x-data="dropzone()">
                                    <div @dragover.prevent="drag=true" @dragleave.prevent="drag=false" @drop.prevent="handleDrop($event)"
                                         :class="{'dropzone-border': true, 'drag-over': drag}"
                                         class="rounded-xl p-6 text-center cursor-pointer"
                                         @click="$refs.fileInput.click()">
                                        <input type="file" x-ref="fileInput" name="image" accept="image/*" @change="handleSelect($event)" class="hidden" />
                                        <template x-if="!preview">
                                            <div class="text-gray-500">
                                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <p class="text-sm">Drop image or click to upload</p>
                                            </div>
                                        </template>
                                        <template x-if="preview">
                                            <img :src="preview" class="max-h-32 mx-auto rounded-lg" />
                                        </template>
                                    </div>
                                </div>
                                <input type="text" name="image_alt" x-model="form.image_alt" maxlength="255" placeholder="Image alt text (optional)" class="mt-2 w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                                <p x-show="errors.image" x-text="errors.image" class="text-red-500 text-xs mt-1"></p>
                            </div>

                            {{-- Banner Image: show existing when editing --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Banner Image</label>
                                <div x-show="editingId && form.existingBannerUrl" class="mb-3">
                                    <img :src="form.existingBannerUrl" alt="Current banner" class="max-h-24 w-full rounded-lg border border-gray-200 object-cover" />
                                    <p class="text-xs text-gray-500 mt-1">Current banner. Choose a new file below to replace.</p>
                                </div>
                                <div x-data="dropzone()">
                                    <div @dragover.prevent="drag=true" @dragleave.prevent="drag=false" @drop.prevent="handleDrop($event)"
                                         :class="{'dropzone-border': true, 'drag-over': drag}"
                                         class="rounded-xl p-6 text-center cursor-pointer"
                                         @click="$refs.fileInput.click()">
                                        <input type="file" x-ref="fileInput" name="banner_image" accept="image/*" @change="handleSelect($event)" class="hidden" />
                                        <template x-if="!preview">
                                            <div class="text-gray-500">
                                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <p class="text-sm">Drop banner or click to upload</p>
                                            </div>
                                        </template>
                                        <template x-if="preview">
                                            <img :src="preview" class="max-h-24 mx-auto rounded-lg w-full object-cover" />
                                        </template>
                                    </div>
                                </div>
                                <input type="text" name="banner_image_alt" x-model="form.banner_image_alt" maxlength="255" placeholder="Banner alt text (optional)" class="mt-2 w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                                <p x-show="errors.banner_image" x-text="errors.banner_image" class="text-red-500 text-xs mt-1"></p>
                            </div>

                            {{-- Icon Select (Lucide searchable) - form is in parent scope --}}
                            <div x-data="{ iconOpen: false, iconSearch: '' }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                                <div class="relative">
                                    <button type="button" @click="iconOpen = !iconOpen" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-left flex items-center gap-2 bg-white">
                                        <span x-show="form.icon"><i :data-lucide="form.icon" class="w-5 h-5 inline"></i></span>
                                        <span x-text="form.icon || 'Select Icon'" :class="form.icon ? '' : 'text-gray-400'"></span>
                                        <svg class="w-4 h-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="iconOpen" @click.away="iconOpen = false" x-cloak class="absolute z-[10000] mt-1 w-full bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-hidden">
                                        <input type="text" x-model="iconSearch" placeholder="Search icons..." class="w-full px-4 py-2 border-b border-gray-100 text-sm focus:ring-0 focus:border-primary" />
                                        <div class="overflow-y-auto max-h-48 p-2">
                                            <template x-for="icon in icons.filter(i => i.includes(iconSearch.toLowerCase()))" :key="icon">
                                                <button type="button" @click="form.icon = icon; iconOpen = false"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 rounded-lg text-sm">
                                                    <i :data-lucide="icon" class="w-4 h-4 flex-shrink-0"></i>
                                                    <span x-text="icon"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="icon" :value="form.icon" />
                            </div>

                            <div class="flex gap-4">
                                <input type="hidden" name="is_active" value="0" />
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_featured" value="1" x-model="form.is_featured" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                                    <span class="text-sm text-gray-700">Featured</span>
                                </label>
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
    Alpine.data('categoryDrawer', () => ({
        open: false,
        editingId: null,
        saving: false,
        errors: {},
        showColumnMenu: false,
        icons: [],
        parentSearch: '',
        filterParentOptions() {
            this.$nextTick(() => {
                const sel = this.$refs.parentSelect;
                if (!sel) return;
                const q = (this.parentSearch || '').toLowerCase();
                Array.from(sel.options).forEach(opt => {
                    if (opt.value === '') { opt.hidden = false; return; }
                    const name = (opt.getAttribute('data-name') || opt.textContent || '').toLowerCase();
                    opt.hidden = q ? name.indexOf(q) === -1 : false;
                });
            });
        },
        columns: {
            id: { label: 'ID', visible: true },
            name: { label: 'Name', visible: true },
            parent: { label: 'Parent', visible: true },
            status: { label: 'Status', visible: true },
            featured: { label: 'Featured', visible: true },
            actions: { label: 'Actions', visible: true }
        },
        form: {
            name: '',
            slug: '',
            description: '',
            parent_id: '',
            icon: '',
            is_active: true,
            is_featured: false,
            existingImageUrl: '',
            existingBannerUrl: '',
            image_alt: '',
            banner_image_alt: '',
            meta_title: '',
            meta_description: '',
            meta_keywords: ''
        },
        baseStorageUrl: '{{ url("storage") }}',
        async openDrawer(id = null) {
            this.editingId = id;
            if (id) {
                const cat = @json($categories->items());
                const c = cat.find(x => x.id == id) || {};
                this.form = {
                    name: c.name || '',
                    slug: c.slug || '',
                    description: c.description || '',
                    parent_id: String(c.parent_id || ''),
                    icon: c.icon || '',
                    is_active: !!c.is_active,
                    is_featured: !!c.is_featured,
                    existingImageUrl: (c.image_url || '') || '',
                    existingBannerUrl: (c.banner_image_url || '') || '',
                    image_alt: c.image_alt || '',
                    banner_image_alt: c.banner_image_alt || '',
                    meta_title: c.meta_title || '',
                    meta_description: c.meta_description || '',
                    meta_keywords: c.meta_keywords || ''
                };
            } else {
                this.form = { name: '', slug: '', description: '', parent_id: '', icon: '', is_active: true, is_featured: false, existingImageUrl: '', existingBannerUrl: '', image_alt: '', banner_image_alt: '', meta_title: '', meta_description: '', meta_keywords: '' };
            }
            this.parentSearch = '';
            this.open = true;
            this.$nextTick(() => {
                lucide.createIcons && lucide.createIcons();
                this.filterParentOptions();
                if (id) {
                    const sel = this.$refs.parentSelect;
                    if (sel) Array.from(sel.options).forEach(opt => { opt.disabled = (opt.value === String(id)); });
                }
            });
        },
        closeDrawer() { this.open = false; },
        async submitForm(e) {
            e.preventDefault();
            this.saving = true;
            this.errors = {};
            const form = e.target;
            const fd = new FormData(form);
            const url = this.editingId ? '{{ url('admin/categories') }}/' + this.editingId : '{{ route('admin.categories.store') }}';
            if (this.editingId) fd.append('_method', 'PUT');
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    }
                });
                let data = {};
                try { data = await r.json(); } catch (_) {}
                if (r.ok && data.success) {
                    this.closeDrawer();
                    window.location.reload();
                    return;
                }
                this.errors = this.flattenErrors(data.errors || {});
                if (data.message) {
                    this.errors.general = data.message;
                } else if (!Object.keys(this.errors).length) {
                    this.errors.general = 'Save failed (HTTP ' + r.status + ').';
                }
            } catch (err) {
                this.errors = { general: err?.message || 'Something went wrong.' };
            }
            this.saving = false;
        },
        flattenErrors(errors) {
            const out = {};
            Object.keys(errors || {}).forEach((key) => {
                const val = errors[key];
                out[key] = Array.isArray(val) ? val.join(' ') : String(val);
            });
            return out;
        },
        async toggleFeatured(id) {
            try {
                const r = await fetch('{{ url('admin/categories') }}/' + id + '/toggle-featured', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
                });
                const data = await r.json();
                if (data.success) window.location.reload();
            } catch (_) {}
        }
    }));

    Alpine.data('dropzone', () => ({
        drag: false,
        preview: null,
        handleDrop(e) {
            this.drag = false;
            const f = e.dataTransfer?.files?.[0];
            if (f && f.type.startsWith('image/')) this.previewFile(f);
        },
        handleSelect(e) {
            const f = e.target.files?.[0];
            if (f) this.previewFile(f);
        },
        previewFile(f) {
            const r = new FileReader();
            r.onload = () => { this.preview = r.result; };
            r.readAsDataURL(f);
        }
    }));
});
</script>
@endsection
