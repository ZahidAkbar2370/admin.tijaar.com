@extends('admin.layouts.app')
@section('title', ($listing ? 'Edit' : 'Create') . ' Listing — Customer #' . $user->id)
@section('admin-content')
@php
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
    $isEdit = $listing !== null;
@endphp
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-3xl">
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-lg font-bold text-gray-900">{{ $isEdit ? 'Edit listing' : 'Create listing' }}</h1>
        <a href="{{ route('admin.users.listings.index', $user) }}" class="text-sm text-primary hover:underline">← Back to listings</a>
    </div>
    <form method="POST" action="{{ $isEdit ? route('admin.users.listings.update', [$user, $listing]) : route('admin.users.listings.store', $user) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Name</label><input type="text" name="name" value="{{ old('name', $listing?->name) }}" required class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Short description</label><input type="text" name="short_description" value="{{ old('short_description', $listing?->short_description) }}" class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Description</label><textarea name="description" rows="4" class="{{ $inputClass }}">{{ old('description', $listing?->description) }}</textarea></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Category</label>
                <select name="category_id" required class="{{ $inputClass }}">
                    <option value="">Select</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $listing?->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Brand</label>
                <select name="brand_id" class="{{ $inputClass }}">
                    <option value="">None</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $listing?->brand_id) == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Price (PKR)</label><input type="number" step="0.01" name="price" value="{{ old('price', $listing?->price) }}" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Compare at price</label><input type="number" step="0.01" name="compare_at_price" value="{{ old('compare_at_price', $listing?->compare_at_price) }}" class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Quantity</label><input type="number" name="quantity" min="0" value="{{ old('quantity', $listing?->quantity ?? 1) }}" required class="{{ $inputClass }}"></div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Condition</label>
                <select name="condition" required class="{{ $inputClass }}">
                    @foreach (['new', 'used', 'refurbished'] as $cond)
                        <option value="{{ $cond }}" @selected(old('condition', $listing?->condition ?? 'new') === $cond)>{{ ucfirst($cond) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Status</label>
                <select name="status" required class="{{ $inputClass }}">
                    @foreach (['draft', 'pending', 'published', 'rejected', 'unpublished'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $listing?->status ?? 'draft') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Shipping</label>
                <select name="shipping_mode" required class="{{ $inputClass }}">
                    <option value="customer_pays" @selected(old('shipping_mode', $listing?->shipping_mode ?? 'customer_pays') === 'customer_pays')>Customer pays</option>
                    <option value="free_shipping" @selected(old('shipping_mode', $listing?->shipping_mode) === 'free_shipping')>Free shipping</option>
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Shipping cost</label><input type="number" step="0.01" name="shipping_cost_cached" value="{{ old('shipping_cost_cached', $listing?->shipping_cost_cached) }}" class="{{ $inputClass }}"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Thumbnail</label><input type="file" name="thumbnail" accept="image/*" class="text-sm"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Images</label><input type="file" name="images[]" accept="image/*" multiple class="text-sm"></div>
        </div>
        @if ($isEdit && $listing?->media?->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($listing->media as $media)
                    <img src="{{ \App\Support\UploadHelper::url($media->path) }}" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                @endforeach
            </div>
        @endif
        <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium">{{ $isEdit ? 'Update listing' : 'Create listing' }}</button>
    </form>
</section>
@endsection
