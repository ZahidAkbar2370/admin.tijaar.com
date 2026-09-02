@php
    $input = $input ?? 'w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none';
    $select = $select ?? $input;
    $typeName = $typeName ?? 'fee_type';
    $valueName = $valueName ?? 'fee_value';
    $typeVal = old($typeName, $settings[$typeName] ?? 'fixed');
    $valueVal = old($valueName, $settings[$valueName] ?? '0');
@endphp
<div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 sm:p-5">
    @if (!empty($label))
        <p class="text-sm font-semibold text-gray-900 mb-1">{{ $label }}</p>
    @endif
    @if (!empty($hint))
        <p class="text-xs text-gray-500 mb-4">{{ $hint }}</p>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
            <select name="{{ $typeName }}" class="{{ $select }}">
                <option value="fixed" @selected($typeVal === 'fixed')>Fixed amount (PKR)</option>
                <option value="percentage" @selected($typeVal === 'percentage')>Percentage (%)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5">Value</label>
            <input type="number" step="0.01" min="0" name="{{ $valueName }}" value="{{ $valueVal }}" class="{{ $input }}">
        </div>
    </div>
</div>
