<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
    public function index(Request $request)
    {
        $query = Category::with('parent')->orderBy('sort_order');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->paginate(20)->withQueryString();
        $tree = Category::tree();
        $parentCategories = Category::root()->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories', 'tree', 'parentCategories'));
    }

    public function create()
    {
        $parents = Category::root()->orderBy('sort_order')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image',
            'banner_image' => 'nullable|image',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'banner_image_alt' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'description', 'parent_id', 'icon', 'meta_title', 'meta_description', 'meta_keywords', 'image_alt', 'banner_image_alt']);
        $data['slug'] = $request->slug ?: Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['sort_order'] = Category::max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeCategoryFile($request->file('image'), 'categories');
        }
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $this->storeCategoryFile($request->file('banner_image'), 'categories');
        }

        Category::create($data);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => 'Category created.', 'redirect' => route('admin.categories.index')]);
        }
        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)->root()->orderBy('sort_order')->get();
        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function json(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image',
            'banner_image' => 'nullable|image',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'image_alt' => 'nullable|string|max:255',
            'banner_image_alt' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'description', 'parent_id', 'icon', 'meta_title', 'meta_description', 'meta_keywords', 'image_alt', 'banner_image_alt']);
        $data['slug'] = $request->slug ?: Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);

        if ($request->hasFile('image')) {
            $this->deleteCategoryFile($category->image);
            $data['image'] = $this->storeCategoryFile($request->file('image'), 'categories');
        }
        if ($request->hasFile('banner_image')) {
            $this->deleteCategoryFile($category->banner_image);
            $data['banner_image'] = $this->storeCategoryFile($request->file('banner_image'), 'categories');
        }

        $category->update($data);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => 'Category updated.', 'redirect' => route('admin.categories.index')]);
        }
        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function toggleFeatured(Category $category)
    {
        $category->update(['is_featured' => !$category->is_featured]);
        return response()->json([
            'success' => true,
            'is_featured' => $category->fresh()->is_featured,
            'message' => $category->is_featured ? 'Category featured.' : 'Category unfeatured.',
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    /**
     * Store uploaded file in public/upload/{subdir}/ and return path for DB (e.g. "upload/categories/abc.jpg").
     */
    protected function storeCategoryFile(\Illuminate\Http\UploadedFile $file, string $subdir): string
    {
        $dir = public_path('upload/' . $subdir);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $name = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);
        return 'upload/' . $subdir . '/' . $name;
    }

    /**
     * Delete file from public if it is under public/upload/.
     */
    protected function deleteCategoryFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $path = ltrim($path, '/');
        if (!Str::startsWith($path, 'upload/')) {
            return;
        }
        $full = public_path($path);
        if (File::isFile($full)) {
            File::delete($full);
        }
    }

    public function export(Request $request)
    {
        $categories = Category::with('parent')->orderBy('sort_order')->get();

        $headers = ['ID', 'Name', 'Slug', 'Parent', 'Active', 'Featured'];
        $rows = [implode(',', $headers)];

        foreach ($categories as $c) {
            $rows[] = implode(',', [
                $c->id,
                '"' . str_replace('"', '""', $c->name) . '"',
                $c->slug,
                $c->parent?->name ?? '—',
                $c->is_active ? 'Yes' : 'No',
                $c->is_featured ? 'Yes' : 'No',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="categories-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
