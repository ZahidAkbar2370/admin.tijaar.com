@extends('admin.layouts.app')
@section('title', ($product ? 'Edit' : 'Create') . ' Product — Seller #' . $user->id)
@section('admin-content')
@php $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm'; $isEdit = $product !== null; @endphp
@include('admin.sellers.partials.seller-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="flex items-center justify-between mb-6"><h1 class="text-xl font-bold">{{ $isEdit ? 'Edit product' : 'Create product' }}</h1><a href="{{ route('admin.sellers.products.index', $user) }}" class="text-sm text-amber-700 hover:underline">← Back to products</a></div>
    <form method="POST" action="{{ $isEdit ? route('admin.sellers.products.update', [$user, $product]) : route('admin.sellers.products.store', $user) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @csrf @if($isEdit) @method('PUT') @endif
        <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Name</label><input type="text" name="name" value="{{ old('name', $product?->name) }}" required class="{{ $inputClass }}"></div>
        <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Short description</label><input type="text" name="short_description" value="{{ old('short_description', $product?->short_description) }}" class="{{ $inputClass }}"></div>
        <div class="sm:col-span-2 lg:col-span-3"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Description</label><textarea name="description" rows="4" class="{{ $inputClass }}">{{ old('description', $product?->description) }}</textarea></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Category</label><select name="category_id" required class="{{ $inputClass }}"><option value="">Select</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('category_id', $product?->category_id)==$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Brand</label><select name="brand_id" class="{{ $inputClass }}"><option value="">None</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id)==$brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Price (PKR)</label><input type="number" step="0.01" name="price" value="{{ old('price', $product?->price) }}" required class="{{ $inputClass }}"></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Quantity</label><input type="number" name="quantity" min="0" value="{{ old('quantity', $product?->quantity ?? 1) }}" required class="{{ $inputClass }}"></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Status</label><select name="status" class="{{ $inputClass }}">@foreach(['draft','pending','published','rejected'] as $st)<option value="{{ $st }}" @selected(old('status', $product?->status ?? 'draft')===$st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Thumbnail</label><input type="file" name="thumbnail" accept="image/*" class="text-sm"></div>
        <div class="sm:col-span-2"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Images</label><input type="file" name="images[]" accept="image/*" multiple class="text-sm"></div>
        <div class="sm:col-span-2 lg:col-span-3"><button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-medium">{{ $isEdit ? 'Update product' : 'Create product' }}</button></div>
    </form>
</section>
@endsection
