<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
    public function index(Request $request)
    {
        $query = Brand::orderBy('sort_order');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $brands = $query->paginate(20)->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'logo_alt' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'description', 'website', 'meta_title', 'meta_description', 'meta_keywords', 'logo_alt']);
        $data['slug'] = $request->slug ?: Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['sort_order'] = Brand::max('sort_order') + 1;

        if ($request->hasFile('logo')) {
            $data['logo'] = UploadHelper::storePublic($request->file('logo'), 'brands');
        }

        Brand::create($data);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => 'Brand created.', 'redirect' => route('admin.brands.index')]);
        }
        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function json(Brand $brand)
    {
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:brands,slug,' . $brand->id,
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'logo_alt' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'description', 'website', 'meta_title', 'meta_description', 'meta_keywords', 'logo_alt']);
        $data['slug'] = $request->slug ?: Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);

        if ($request->hasFile('logo')) {
            $data['logo'] = UploadHelper::storePublic($request->file('logo'), 'brands');
        }

        $brand->update($data);

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => 'Brand updated.', 'redirect' => route('admin.brands.index')]);
        }
        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }

    public function export(Request $request)
    {
        $brands = Brand::orderBy('sort_order')->get();

        $headers = ['ID', 'Name', 'Slug', 'Website', 'Active', 'Featured'];
        $rows = [implode(',', $headers)];

        foreach ($brands as $b) {
            $rows[] = implode(',', [
                $b->id,
                '"' . str_replace('"', '""', $b->name) . '"',
                $b->slug,
                $b->website ?? '—',
                $b->is_active ? 'Yes' : 'No',
                $b->is_featured ? 'Yes' : 'No',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="brands-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
