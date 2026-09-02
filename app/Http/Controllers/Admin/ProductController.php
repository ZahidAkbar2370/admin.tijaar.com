<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductSeoHelper;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['store.seller.user', 'sellerUser', 'category.parent', 'brand', 'media'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('subcategory_id')) {
            $query->where('category_id', $request->subcategory_id);
        } elseif ($request->filled('category_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('category_id', $request->category_id)
                    ->orWhereHas('category', fn ($c) => $c->where('parent_id', $request->category_id));
            });
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $products = $query->paginate(20)->withQueryString();

        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $subcategories = $request->filled('category_id')
            ? Category::where('parent_id', $request->category_id)->orderBy('name')->get()
            : collect();
        $stores = Store::with('seller.user')->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'subcategories', 'stores'));
    }

    public function show(Product $product)
    {
        $product->load(['store.seller.user', 'sellerUser', 'category.parent', 'brand', 'media']);

        $seo = ProductSeoHelper::resolve([
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
        ]);

        return view('admin.products.show', compact('product', 'seo'));
    }

    public function updateStatus(Request $request, Product $product)
    {
        $request->validate(['status' => 'required|in:draft,pending,published,rejected']);
        $product->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Product status updated to ' . $request->status . '.');
    }

    public function approve(Product $product)
    {
        $product->update(['status' => 'published']);
        return redirect()->back()->with('success', 'Product approved and published.');
    }

    public function reject(Product $product)
    {
        $product->update(['status' => 'rejected']);
        return redirect()->back()->with('success', 'Product rejected.');
    }

    public function updateSeo(Request $request, Product $product)
    {
        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $meta = ProductSeoHelper::resolve(array_merge([
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
        ], $validated));

        $product->update($meta);

        return redirect()->back()->with('success', 'Product SEO updated.');
    }

    public function export(Request $request)
    {
        $products = Product::with(['store', 'category', 'brand'])->orderBy('created_at', 'desc')->get();

        $headers = ['ID', 'Name', 'SKU', 'Store', 'Category', 'Price', 'Status'];
        $rows = [implode(',', $headers)];

        foreach ($products as $p) {
            $rows[] = implode(',', [
                $p->id,
                '"' . str_replace('"', '""', $p->name) . '"',
                $p->sku ?? '—',
                '"' . str_replace('"', '""', $p->store?->name ?? '—') . '"',
                $p->category?->name ?? '—',
                $p->price,
                $p->status,
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="products-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
