@php
    $sectionList = array_values($sections['sections'] ?? []);
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ $sectionsTitle ?? 'Page sections' }}</h2>
            <p class="text-sm text-gray-500 mt-1">Use <strong>Add section</strong> or <strong>Edit</strong> — modal <strong>Save section</strong> saves directly to the database.</p>
        </div>
        <button type="button" class="js-open-section-modal px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-xl shadow-sm transition flex items-center gap-2" data-prefix="{{ $prefix }}" data-index="-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add section
        </button>
    </div>

    @if (count($sectionList) === 0)
        <div class="px-6 py-12 text-center text-gray-500 text-sm">
            No sections yet. Click &quot;Add section&quot; to create one.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">Order</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider w-56">Title</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider w-52 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectionList as $i => $sec)
                        @php
                            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($sec['content'] ?? '')));
                            $preview = $plain === '' ? '—' : (\Illuminate\Support\Str::limit($plain, 120));
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $sec['title'] ?: '(No title)' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $preview }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                @if ($i > 0)
                                    <button type="submit" formaction="{{ route('admin.pages.sections.move', [$page, $i, 'up']) }}" formmethod="POST" class="px-2 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 rounded-lg transition mr-1" title="Move up">↑</button>
                                @endif
                                @if ($i < count($sectionList) - 1)
                                    <button type="submit" formaction="{{ route('admin.pages.sections.move', [$page, $i, 'down']) }}" formmethod="POST" class="px-2 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 rounded-lg transition mr-1" title="Move down">↓</button>
                                @endif
                                <button type="button" class="js-open-section-modal px-2 py-1.5 text-xs font-medium text-primary hover:bg-primary/10 rounded-lg transition mr-1" data-prefix="{{ $prefix }}" data-index="{{ $i }}">Edit</button>
                                <button type="submit" formaction="{{ route('admin.pages.sections.delete', [$page, $i]) }}" formmethod="POST" class="px-2 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition" onclick="return confirm('Remove this section from the database?');">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script type="application/json" id="{{ $prefix }}-sections-data">@json($sectionList)</script>
