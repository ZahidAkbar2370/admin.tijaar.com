@php
    /** @var string $name */
    /** @var string $label */
    $help = $help ?? '';
    $checked = (string) ($value ?? '0') === '1';
@endphp
<div class="flex items-start justify-between gap-4 py-3">
    <div>
        <p class="font-medium text-gray-900 text-sm">{{ $label }}</p>
        @if ($help)
            <p class="text-xs text-gray-500 mt-1">{{ $help }}</p>
        @endif
    </div>
    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" {{ $checked ? 'checked' : '' }} class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
    </label>
</div>
