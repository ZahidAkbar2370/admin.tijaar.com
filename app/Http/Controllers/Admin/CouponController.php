<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $query = Coupon::with('store')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where('code', 'like', "%{$q}%");
        }
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        $coupons = $query->paginate(20)->withQueryString();
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $stores = Store::all();
        $categories = Category::active()->orderBy('name')->get();
        return view('admin.coupons.create', compact('stores', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:32',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'scope' => 'required|in:platform,store',
            'store_id' => 'nullable|required_if:scope,store|exists:stores,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $coupon = Coupon::create([
            'code' => strtoupper(trim($request->code)),
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_discount' => $request->max_discount,
            'max_uses' => $request->max_uses,
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
            'scope' => $request->scope,
            'store_id' => $request->scope === 'store' ? $request->store_id : null,
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);

        if ($request->filled('category_ids')) {
            $coupon->categories()->sync($request->category_ids);
        }
        if ($request->filled('product_ids')) {
            $coupon->products()->sync($request->product_ids);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        $stores = Store::all();
        $categories = Category::active()->orderBy('name')->get();
        $coupon->load(['categories', 'products']);
        return view('admin.coupons.edit', compact('coupon', 'stores', 'categories'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'code' => 'required|string|max:32',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'scope' => 'required|in:platform,store',
            'store_id' => 'nullable|required_if:scope,store|exists:stores,id',
            'is_active' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $coupon->update([
            'code' => strtoupper(trim($request->code)),
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_discount' => $request->max_discount,
            'max_uses' => $request->max_uses,
            'valid_from' => $request->valid_from,
            'valid_to' => $request->valid_to,
            'scope' => $request->scope,
            'store_id' => $request->scope === 'store' ? $request->store_id : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $coupon->categories()->sync($request->category_ids ?? []);
        $coupon->products()->sync($request->product_ids ?? []);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }
}
