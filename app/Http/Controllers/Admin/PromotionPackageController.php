<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionPackageController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $packages = PromotionPackage::orderBy('sort_order')->orderBy('id')->paginate(20);
        return view('admin.promotion-packages.index', compact('packages'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('admin.promotion-packages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:featured_product,hot_sale,featured_shop,store_banner',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        PromotionPackage::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
            'type' => $request->type,
            'description' => $request->description,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'seller_type_eligibility' => 'both',
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.promotion-packages.index')->with('success', 'Package created.');
    }

    public function edit(PromotionPackage $promotionPackage): \Illuminate\View\View
    {
        return view('admin.promotion-packages.edit', compact('promotionPackage'));
    }

    public function update(Request $request, PromotionPackage $promotionPackage)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:featured_product,hot_sale,featured_shop,store_banner',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $promotionPackage->update([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.promotion-packages.index')->with('success', 'Package updated.');
    }
}
