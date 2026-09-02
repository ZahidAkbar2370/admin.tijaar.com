@extends('admin.layouts.app')

@section('title', 'Shipping Zones')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Shipping Zones</h1>
        <p class="text-sm text-gray-500 mt-1">Pakistan & UAE zones and rules</p>
    </div>
    <a href="{{ route('admin.shipping-zones.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Zone
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50/80">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Zone</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Market</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Country</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Rules</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($zones as $z)
            <tr class="hover:bg-gray-50/50 transition">
                <td class="px-6 py-4 font-medium">{{ $z->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $z->market }}</td>
                <td class="px-6 py-4 text-sm">{{ $z->country ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $z->rules_count ?? 0 }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.shipping-zones.edit', $z) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition">Edit</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-16 text-center text-gray-500">No zones. Run ShippingZoneSeeder or add manually.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($zones->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $zones->links() }}</div>
    @endif
</div>
@endsection
