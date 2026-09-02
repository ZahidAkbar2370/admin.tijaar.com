@props(['name', 'label' => 'Image alt text (optional)', 'value' => '', 'id' => null])
<div class="mt-2">
    <label @if($id) for="{{ $id }}" @endif class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
    <input type="text" name="{{ $name }}" @if($id) id="{{ $id }}" @endif value="{{ old($name, $value) }}" maxlength="255"
        placeholder="Describe the image for screen readers"
        class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
</div>
