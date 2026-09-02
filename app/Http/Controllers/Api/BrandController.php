<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::active()->orderBy('sort_order')->orderBy('name');

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->category_id;
            // Include brands for this category and its parent (subcategory → parent brands)
            $ids = [$categoryId];
            $cat = \App\Models\Category::find($categoryId);
            if ($cat?->parent_id) {
                $ids[] = (int) $cat->parent_id;
            }
            $query->whereIn('category_id', $ids);
        }

        if ($request->filled('search')) {
            $q = \Illuminate\Support\Str::limit($request->search, 255, '');
            $query->where('name', 'like', '%' . $q . '%');
        }

        $brands = $query->get();

        return response()->json([
            'success' => true,
            'brands' => $brands,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)->active()->first();

        if (!$brand) {
            return response()->json(['success' => false, 'message' => 'Brand not found'], 404);
        }

        return response()->json([
            'success' => true,
            'brand' => $brand,
        ]);
    }

    public function featured(): JsonResponse
    {
        $brands = Brand::active()->featured()->orderBy('sort_order')->limit(12)->get();
        return response()->json(['success' => true, 'brands' => $brands]);
    }
}
