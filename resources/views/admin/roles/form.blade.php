@extends('admin.layouts.app')

@section('title', $role ? 'Edit role' : 'Create role')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">← Back to Roles</a>
</div>

<div class="w-full min-w-0 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6">{{ $role ? 'Edit role' : 'Create role' }}</h2>
    <form method="POST" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if ($role) @method('PUT') @endif
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $role?->name) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required />
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $role?->slug) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required />
                @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Description</label>
                <input type="text" name="description" value="{{ old('description', $role?->description) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Permissions</label>
                <div class="space-y-3 max-h-64 overflow-y-auto border border-gray-100 rounded-xl p-4">
                    @foreach ($permissions as $module => $perms)
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">{{ $module ?? 'General' }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($perms as $p)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg text-sm cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ ($role && $role->permissions->contains($p)) || in_array($p->id, old('permissions', [])) ? 'checked' : '' }} />
                                <span>{{ $p->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Save</button>
            <a href="{{ route('admin.roles.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
