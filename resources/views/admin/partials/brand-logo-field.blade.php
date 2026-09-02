@props([
    'inputName',
    'logoUrl',
    'removeName' => null,
    'help' => 'PNG, JPG, WEBP or SVG up to 2 MB. A default logo is shown until you upload one.',
])

<div class="flex flex-col sm:flex-row sm:items-start gap-4">
    <div class="w-24 h-16 rounded-xl border border-gray-200 bg-white flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
        <img src="{{ $logoUrl }}" alt="" class="max-w-full max-h-full object-contain p-2">
    </div>
    <div class="flex-1 min-w-0">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Logo</label>
        <input
            type="file"
            name="{{ $inputName }}"
            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.svg"
            class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/15"
        />
        @if ($removeName)
            <label class="inline-flex items-center gap-2 mt-2.5 text-xs text-gray-600 cursor-pointer">
                <input type="hidden" name="{{ $removeName }}" value="0" />
                <input type="checkbox" name="{{ $removeName }}" value="1" class="rounded border-gray-300 text-primary focus:ring-primary/30" />
                Remove custom logo (use default)
            </label>
        @endif
        <p class="text-xs text-gray-500 mt-2">{{ $help }}</p>
    </div>
</div>
