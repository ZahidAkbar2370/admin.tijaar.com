@if ($paginator->hasPages())
<div class="px-5 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-gray-600">
        Showing <span class="font-medium text-gray-900">{{ $paginator->firstItem() ?? 0 }}</span>–<span class="font-medium text-gray-900">{{ $paginator->lastItem() ?? 0 }}</span> of <span class="font-medium text-gray-900">{{ $paginator->total() }}</span>
    </p>
    <div class="flex items-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-gray-400 cursor-not-allowed text-sm font-medium">← Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:border-primary/30 text-sm font-medium transition">← Previous</a>
        @endif
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-primary bg-primary text-white hover:bg-primary-dark text-sm font-medium transition">Next →</a>
        @else
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-gray-400 cursor-not-allowed text-sm font-medium">Next →</span>
        @endif
    </div>
</div>
@endif
