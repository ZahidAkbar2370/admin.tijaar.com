@extends('admin.layouts.app')

@section('title', 'Activity Log')

@section('admin-content')
<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
            <p class="text-sm text-gray-500 mt-1">Track who changed what across frontend API and admin panel</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form method="GET" action="{{ route('admin.activities.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="User, description, IP, table..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <div class="w-44">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Action type</label>
                    <select name="action_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All types</option>
                        @foreach ($actionTypes as $type)
                            <option value="{{ $type }}" {{ request('action_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Target table</label>
                    <select name="target_table" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                        <option value="">All tables</option>
                        @foreach ($targetTables as $table)
                            <option value="{{ $table }}" {{ request('target_table') === $table ? 'selected' : '' }}>{{ $table }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">User ID</label>
                    <input type="number" name="action_by" value="{{ request('action_by') }}" placeholder="e.g. 12" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">IP</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}" placeholder="IP address" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'action_type', 'target_table', 'action_by', 'date_from', 'date_to', 'ip_address']))
                    <a href="{{ route('admin.activities.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">When</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Table</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Location</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($activities as $activity)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $activity->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                {{ $activity->created_at?->timezone(config('app.timezone', 'UTC'))->format('Y-m-d g:i A') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if ($activity->actor)
                                    <div class="font-medium text-gray-900">{{ $activity->actor->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $activity->actor->email }} · #{{ $activity->action_by }} · {{ $activity->actor->role }}</div>
                                @elseif ($activity->action_by)
                                    <span class="text-gray-600">User #{{ $activity->action_by }}</span>
                                @else
                                    <span class="text-gray-400">Guest / System</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-primary/10 text-primary">{{ $activity->action_type }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-mono text-xs">{{ $activity->target_table ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-mono text-xs">{{ $activity->action_on ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $activity->description }}">{{ $activity->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $activity->device ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">{{ $activity->ip_address ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $activity->location ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">No activities found. Actions will appear here as users and admins use the system.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
