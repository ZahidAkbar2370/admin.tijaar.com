<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use App\Services\Admin\SellerAdminService;
use App\Support\ProductSeoHelper;
use App\Support\UploadHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SellerProductController extends Controller
{
    public function index(Request $request, User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $query = Product::withTrashed()
            ->where('seller_type', 'business')
            ->where('seller_id', $user->id)
            ->with(['category'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }

        $products = $query->paginate(15)->withQueryString();

        return view('admin.sellers.products.index', compact('user', 'products'));
    }

    public function create(User $user): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller.store');
        if (! $user->seller?->store) {
            return redirect()->route('admin.sellers.storefront', $user)->with('error', 'Create a store before adding products.');
        }

        return view('admin.sellers.products.form', [
            'user' => $user,
            'product' => null,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }

        $user->load('seller.store');
        $store = $user->seller?->store;
        if (! $store) {
            return redirect()->route('admin.sellers.storefront', $user)->with('error', 'Store required.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:draft,pending,published,rejected',
            'images.*' => 'nullable|image|max:4096',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        $meta = ProductSeoHelper::resolve(array_merge($request->all(), ['_auto_seo' => true]));

        $product = Product::create([
            'seller_type' => 'business',
            'seller_id' => $user->id,
            'store_id' => $store->id,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'sku' => $this->uniqueSku(),
            'name' => $request->name,
            'slug' => ProductSeoHelper::uniqueSlug((string) $request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'condition' => 'new',
            'status' => $request->status,
            'product_type' => 'simple',
            'meta_title' => $meta['meta_title'],
            'meta_description' => $meta['meta_description'],
            'meta_keywords' => $meta['meta_keywords'],
        ]);

        $this->syncImages($request, $product);

        return redirect()->route('admin.sellers.products.index', $user)->with('success', 'Product created.');
    }

    public function edit(User $user, Product $product): View|RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }
        if ($err = $this->assertProduct($user, $product)) {
            return $err;
        }

        $product->load(['category', 'media']);

        return view('admin.sellers.products.form', [
            'user' => $user,
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user, Product $product): RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }
        if ($err = $this->assertProduct($user, $product)) {
            return $err;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|in:draft,pending,published,rejected',
            'images.*' => 'nullable|image|max:4096',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        $product->fill($request->only(['name', 'description', 'short_description', 'category_id', 'brand_id', 'price', 'quantity', 'status']));
        if ($product->isDirty('name')) {
            $product->slug = ProductSeoHelper::uniqueSlug((string) $product->name, (int) $product->id);
        }
        $meta = ProductSeoHelper::resolve(array_merge($product->toArray(), ['_auto_seo' => true]));
        $product->meta_title = $meta['meta_title'];
        $product->meta_description = $meta['meta_description'];
        $product->meta_keywords = $meta['meta_keywords'];
        $product->save();

        $this->syncImages($request, $product);

        return redirect()->route('admin.sellers.products.index', $user)->with('success', 'Product updated.');
    }

    public function destroy(User $user, Product $product): RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }
        if ($err = $this->assertProduct($user, $product)) {
            return $err;
        }

        $product->delete();

        return redirect()->route('admin.sellers.products.index', $user)->with('success', 'Product deleted.');
    }

    public function updateStatus(Request $request, User $user, Product $product): RedirectResponse
    {
        if ($redirect = SellerAdminService::ensureSeller($user)) {
            return $redirect;
        }
        if ($err = $this->assertProduct($user, $product)) {
            return $err;
        }

        $request->validate(['status' => 'required|in:draft,pending,published,rejected']);
        $product->update(['status' => $request->status]);

        return redirect()->route('admin.sellers.products.index', $user)->with('success', 'Status updated.');
    }

    private function assertProduct(User $user, Product $product): ?RedirectResponse
    {
        if ($product->seller_type !== 'business' || (int) $product->seller_id !== (int) $user->id) {
            return redirect()->route('admin.sellers.products.index', $user)->with('error', 'Product not found.');
        }

        return null;
    }

    private function syncImages(Request $request, Product $product): void
    {
        $thumbnailFile = $request->file('thumbnail');
        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $product->update(['thumbnail_path' => UploadHelper::storePublic($thumbnailFile, 'products/' . $product->id)]);
        }
        if ($request->hasFile('images')) {
            $product->media()->delete();
            foreach ($request->file('images') as $i => $file) {
                if (! $file?->isValid()) {
                    continue;
                }
                ProductMedia::create([
                    'product_id' => $product->id,
                    'type' => 'image',
                    'path' => UploadHelper::storePublic($file, 'products/' . $product->id),
                    'sort_order' => $i,
                ]);
            }
        }
    }

    private function uniqueSku(): string
    {
        do {
            $sku = 'BIZ-' . strtoupper(Str::random(10));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
