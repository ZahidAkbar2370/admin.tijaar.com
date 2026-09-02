@extends('admin.layouts.app')

@section('title', 'Roles')

@section('admin-content')
@include('admin.partials.settings-flash')

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Roles</h1>
        <p class="text-sm text-gray-500 mt-1">Manage roles and assign permissions. System roles cannot be edited or deleted.</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Create role</a>
</div>

<div class="mb-4">
    <a href="{{ route('admin.roles.permissions-matrix') }}" class="text-primary hover:underline text-sm font-medium">View permissions matrix →</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50/80">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Name</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Slug</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Permissions</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Users</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($roles as $role)
            @php $protected = \App\Models\Role::isProtected($role); @endphp
            <tr class="hover:bg-gray-50/50 transition">
                <td class="px-6 py-4 font-medium text-gray-900">
                    {{ $role->name }}
                    @if ($protected)
                        <span class="ml-2 inline-flex px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-600">System</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $role->slug }}</td>
                <td class="px-6 py-4 text-sm">{{ $role->permissions_count }}</td>
                <td class="px-6 py-4 text-sm">{{ $role->users_count }}</td>
                <td class="px-6 py-4 text-right space-x-3">
                    @if ($protected)
                        <span class="text-xs text-gray-400">Locked</span>
                    @else
                        <a href="{{ route('admin.roles.edit', $role) }}" class="text-primary hover:underline text-sm font-medium">Edit</a>
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Delete this role?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-sm font-medium">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
