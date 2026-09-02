<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeFeaturedCategory;
use App\Models\HomeFeaturedProduct;
use App\Support\HomeCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeFeaturedController extends Controller
{
    public function index(): View
    {
        $selectedCategoryIds = HomeFeaturedCategory::orderBy('sort_order')->pluck('category_id')->toArray();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);
        $selectedCategories = collect();
        if (!empty($selectedCategoryIds)) {
            $selectedCategories = Category::whereIn('id', $selectedCategoryIds)
                ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $selectedCategoryIds)) . ')')
                ->get();
        }

        return view('admin.home-featured.index', compact(
            'categories',
            'selectedCategoryIds',
            'selectedCategories'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $categoryIds = array_filter(array_map('intval', (array) $request->input('category_ids', [])));

        HomeFeaturedCategory::query()->delete();
        foreach ($categoryIds as $i => $id) {
            if (Category::where('id', $id)->exists()) {
                HomeFeaturedCategory::create(['category_id' => $id, 'sort_order' => $i]);
            }
        }

        HomeFeaturedProduct::query()->delete();

        HomeCache::clear();

        return redirect()->route('admin.home-featured.index')->with('success', 'Home display saved. Selected categories and all their products will appear on the home page.');
    }
}
