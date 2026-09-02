@extends('admin.layouts.app')

@section('title', 'Permissions Matrix')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">← Back to Roles</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6">Permissions matrix</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Permission</th>
                    @foreach ($roles as $role)
                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase">{{ $role->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $module => $perms)
                @foreach ($perms as $p)
                <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                    <td class="px-4 py-2 text-gray-700">{{ $p->name }} <span class="text-gray-400">({{ $p->slug }})</span></td>
                    @foreach ($roles as $role)
                    <td class="px-4 py-2 text-center">
                        @if ($role->permissions->contains($p))
                            <span class="text-emerald-600 font-medium">✓</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
