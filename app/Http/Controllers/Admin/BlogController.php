<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Support\RichTextHelper;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Blog::with('author:id,name')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%"));
        }

        $blogs = $query->paginate(20)->withQueryString();
        return view('admin.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('admin.blogs.form', ['blog' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['author_id'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published', false);
        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = UploadHelper::storePublic($request->file('featured_image'), 'blogs');
        } else {
            unset($data['featured_image']);
        }

        if (isset($data['content'])) {
            $data['content'] = RichTextHelper::cleanHtml($data['content']);
        }

        Blog::create($data);
        return redirect()->route('admin.blogs.index')->with('success', 'Blog created.');
    }

    public function edit(Blog $blog): View
    {
        return view('admin.blogs.form', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $blog->id,
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $data['is_published'] = $request->boolean('is_published', false);
        if ($data['is_published'] && empty($data['published_at']) && !$blog->published_at) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image') && $request->file('featured_image')->isValid()) {
            if ($blog->featured_image) {
                UploadHelper::deleteAny($blog->featured_image);
            }
            $data['featured_image'] = UploadHelper::storePublic($request->file('featured_image'), 'blogs');
        } else {
            unset($data['featured_image']);
        }

        if (isset($data['content'])) {
            $data['content'] = RichTextHelper::cleanHtml($data['content']);
        }

        $blog->update($data);
        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->featured_image) {
            UploadHelper::deleteAny($blog->featured_image);
        }
        $blog->delete();
        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted.');
    }
}
