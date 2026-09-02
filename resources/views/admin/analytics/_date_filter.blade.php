<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" action="{{ $route }}" class="flex flex-wrap items-end gap-4">
        @foreach(request()->except(['period','date_from','date_to']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Period</label>
                <select name="period" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
                    <option value="7" {{ ($period ?? 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ ($period ?? 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ ($period ?? 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">To</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Apply</button>
        </div>
    </form>
    <p class="text-xs text-gray-500 mt-2">Showing {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</p>
</div>
