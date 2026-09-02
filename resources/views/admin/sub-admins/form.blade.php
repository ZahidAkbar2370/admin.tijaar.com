@extends('admin.layouts.app')

@section('title', $user ? 'Edit Sub-Admin' : 'Create Sub-Admin')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.sub-admins.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">← Back to Sub-Admins</a>
</div>

<div class="w-full min-w-0 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6">{{ $user ? 'Edit Sub-Admin' : 'Create Sub-Admin' }}</h2>
    <form method="POST" action="{{ $user ? route('admin.sub-admins.update', $user) : route('admin.sub-admins.store') }}">
        @csrf
        @if ($user) @method('PUT') @endif
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $user?->name) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required />
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required />
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Password {{ $user ? '(leave blank to keep)' : '' }}</label>
                <input type="password" name="password" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" {{ $user ? '' : 'required' }} />
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @if (!$user)
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Confirm password</label>
                <input type="password" name="password_confirmation" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Role</label>
                <select name="role_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" required>
                    @foreach ($roles as $r)
                    <option value="{{ $r->id }}" {{ ($user && $user->roles->first()?->id === $r->id) || old('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
                @error('role_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Save</button>
            <a href="{{ route('admin.sub-admins.index') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
