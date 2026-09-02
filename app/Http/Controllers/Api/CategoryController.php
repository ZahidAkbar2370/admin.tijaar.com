<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::active()->orderBy('sort_order');

        if ($request->boolean('tree')) {
            $categories = Category::with(['children' => function ($q) {
                $q->active()->orderBy('sort_order')->with(['children' => function ($q2) {
                    $q2->active()->orderBy('sort_order');
                }]);
            }])->active()->root()->orderBy('sort_order')->get();
        } else {
            $categories = $query->get();
        }

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::with(['children' => function ($q) {
            $q->active()->orderBy('sort_order');
        }, 'parent', 'attributes'])->where('slug', $slug)->active()->first();

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }

    public function featured(): JsonResponse
    {
        $categories = Category::active()->featured()->orderBy('sort_order')->limit(12)->get();
        return response()->json(['success' => true, 'categories' => $categories]);
    }
}
