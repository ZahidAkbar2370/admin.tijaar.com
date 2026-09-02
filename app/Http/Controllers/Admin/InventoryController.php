<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function lowStock(Request $request): View
    {
        $query = Product::with(['store.seller', 'category', 'media', 'sellerUser'])
            ->where('track_inventory', true)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0)
            ->orderBy('quantity');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        $products = $query->paginate(30)->withQueryString();
        return view('admin.inventory.low-stock', compact('products'));
    }

    public function outOfStock(Request $request): View
    {
        $query = Product::with(['store.seller', 'category', 'media', 'sellerUser', 'variants'])
            ->where(function ($q) {
                $q->where('track_inventory', true)->orWhereNull('track_inventory');
            })
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                    })->where('quantity', '<=', 0);
                })->orWhere(function ($q2) {
                    $q2->where('product_type', 'variable')
                        ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) <= 0');
                });
            })
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }

        $products = $query->paginate(30)->withQueryString();
        return view('admin.inventory.out-of-stock', compact('products'));
    }
}
