@extends('admin.layouts.app')

@section('title', 'Blogs')

@section('admin-content')
@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">{{ session('success') }}</div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Blogs</h1>
        <p class="text-sm text-gray-500 mt-1">Manage blog posts</p>
    </div>
    <a href="{{ route('admin.blogs.create') }}" class="px-4 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Create Blog</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.blogs.index') }}" class="p-5 border-b border-gray-100 bg-gray-50/50">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm">Filter</button>
        </div>
    </form>
    <div class="divide-y divide-gray-100">
        @forelse ($blogs as $b)
        <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if ($b->featured_image)
                <img src="{{ \App\Support\UploadHelper::url($b->featured_image) }}" alt="" class="w-16 h-12 object-cover rounded" />
                @else
                <div class="w-16 h-12 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-xs">No image</div>
                @endif
                <div>
                    <p class="font-medium text-gray-900">{{ $b->title }}</p>
                    <p class="text-sm text-gray-500">{{ $b->author?->name }} • {{ $b->is_published ? 'Published' : 'Draft' }} • {{ $b->created_at->format('M d, Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blogs.edit', $b) }}" class="px-3 py-1.5 text-sm text-primary hover:bg-primary/10 rounded-lg">Edit</a>
                <form method="POST" action="{{ route('admin.blogs.destroy', $b) }}" onsubmit="return sweetConfirm(event, 'Delete this blog post? This cannot be undone.', 'Delete blog?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-16 text-center text-gray-500">No blogs</div>
        @endforelse
    </div>
    @if ($blogs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $blogs->links() }}</div>
    @endif
</div>
@endsection
