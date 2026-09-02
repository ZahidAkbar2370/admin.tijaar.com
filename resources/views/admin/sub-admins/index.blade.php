@extends('admin.layouts.app')

@section('title', 'Sub-Admins')

@section('admin-content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sub-Admins</h1>
        <p class="text-sm text-gray-500 mt-1">Manage staff with restricted permissions</p>
    </div>
    <a href="{{ route('admin.sub-admins.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Create Sub-Admin</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50/80">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Name</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Email</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Role</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($subAdmins as $u)
            <tr class="hover:bg-gray-50/50 transition">
                <td class="px-6 py-4 font-medium text-gray-900">{{ $u->name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $u->email }}</td>
                <td class="px-6 py-4 text-sm">{{ $u->roles->first()?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.sub-admins.edit', $u) }}" class="text-primary hover:underline text-sm font-medium">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">No sub-admins</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($subAdmins->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $subAdmins->links() }}</div>
    @endif
</div>
@endsection
