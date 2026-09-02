@extends('admin.layouts.app')

@section('title', 'Flagged Items')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.abuse-safety.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary text-sm font-medium">← Back to Abuse & Safety</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.abuse-safety.flagged') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Reported by</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($flagged as $f)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 text-sm">{{ $f->flaggable_type }} #{{ $f->flaggable_id }}</td>
                    <td class="px-6 py-4 text-sm">{{ $f->reason ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $f->reporter?->email ?? '—' }}</td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $f->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700' }}">{{ $f->status }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $f->created_at?->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No flagged items</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($flagged->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $flagged->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
