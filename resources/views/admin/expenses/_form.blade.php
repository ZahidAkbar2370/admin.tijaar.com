@php
    $input = 'w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
    $select = $input;
    $expense = $expense ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">Expense title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $expense?->title) }}" required maxlength="255" class="{{ $input }}" placeholder="e.g. JazzCash monthly gateway fee" />
        @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Category of expense <span class="text-red-500">*</span></label>
        <select name="category" required class="{{ $select }}">
            <option value="">Select category</option>
            @foreach ($categories as $key => $label)
                <option value="{{ $key }}" @selected(old('category', $expense?->category) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Amount (PKR) <span class="text-red-500">*</span></label>
        <input type="number" name="amount" value="{{ old('amount', $expense?->amount) }}" step="0.01" min="0.01" required class="{{ $input }}" />
        @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Expense date</label>
        <input type="date" name="expense_date" value="{{ old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}" />
        @error('expense_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Proof image</label>
        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/15" />
        <p class="text-xs text-gray-500 mt-1.5">Receipt, invoice, or payment screenshot (max 5 MB).</p>
        @error('proof_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        @if ($expense?->proof_image)
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <a href="{{ $expense->proof_image_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm text-primary hover:underline">
                    View current proof
                </a>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_proof_image" value="1" class="rounded border-gray-300 text-primary focus:ring-primary/30" />
                    Remove current image
                </label>
            </div>
        @endif
    </div>
</div>

<div class="mt-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
    <textarea name="description" rows="4" maxlength="5000" class="{{ $input }}" placeholder="Optional notes about this expense">{{ old('description', $expense?->description) }}</textarea>
    @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
</div>
