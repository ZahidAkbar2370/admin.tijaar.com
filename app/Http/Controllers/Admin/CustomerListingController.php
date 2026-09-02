<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\CustomerAdminService;
use App\Support\ProductSeoHelper;
use App\Support\UploadHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerListingController extends Controller
{
    public function index(Request $request, User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $query = Product::withTrashed()
            ->where('seller_type', 'private')
            ->where('seller_id', $user->id)
            ->with(['category'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $listings = $query->paginate(15)->withQueryString();

        return view('admin.users.listings.index', compact('user', 'listings'));
    }

    public function create(User $user): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('admin.users.listings.form', [
            'user' => $user,
            'listing' => null,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0|max:9999',
            'condition' => 'required|in:new,used,refurbished',
            'status' => 'required|in:draft,pending,published,rejected,unpublished',
            'shipping_mode' => 'required|in:free_shipping,customer_pays',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $meta = ProductSeoHelper::resolve(array_merge($request->all(), ['_auto_seo' => true]));
        $expiryDays = (int) Setting::get('private_listing_expiry_days', '30');
        $status = $request->status;
        $publishedStatuses = ['published', 'pending'];

        $product = Product::create([
            'seller_type' => 'private',
            'seller_id' => $user->id,
            'store_id' => null,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'sku' => $this->uniquePrivateSku(),
            'name' => $request->name,
            'slug' => ProductSeoHelper::uniqueSlug((string) $request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'compare_at_price' => $request->compare_at_price ?: null,
            'quantity' => $request->quantity,
            'condition' => $request->condition,
            'status' => $status,
            'expires_at' => (in_array($status, $publishedStatuses, true) && $expiryDays > 0) ? now()->addDays($expiryDays) : null,
            'product_type' => 'simple',
            'weight_kg' => $request->input('weight_kg', 0.5),
            'shipping_mode' => $request->shipping_mode,
            'shipping_cost_cached' => $request->shipping_mode === 'customer_pays'
                ? (float) $request->input('shipping_cost_cached', 0)
                : 0,
            'meta_title' => $meta['meta_title'],
            'meta_description' => $meta['meta_description'],
            'meta_keywords' => $meta['meta_keywords'],
        ]);

        $this->syncImages($request, $product);

        return redirect()
            ->route('admin.users.listings.index', $user)
            ->with('success', 'Listing created.');
    }

    public function edit(User $user, Product $listing): View|RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }
        if ($err = $this->assertListing($user, $listing)) {
            return $err;
        }

        $listing->load(['category', 'media']);
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('admin.users.listings.form', [
            'user' => $user,
            'listing' => $listing,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function update(Request $request, User $user, Product $listing): RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }
        if ($err = $this->assertListing($user, $listing)) {
            return $err;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0|max:9999',
            'condition' => 'required|in:new,used,refurbished',
            'status' => 'required|in:draft,pending,published,rejected,unpublished,removed',
            'shipping_mode' => 'required|in:free_shipping,customer_pays',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $listing->fill([
            'name' => $request->name,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'price' => $request->price,
            'compare_at_price' => $request->compare_at_price ?: null,
            'quantity' => $request->quantity,
            'condition' => $request->condition,
            'status' => $request->status,
            'shipping_mode' => $request->shipping_mode,
            'shipping_cost_cached' => $request->shipping_mode === 'customer_pays'
                ? (float) $request->input('shipping_cost_cached', 0)
                : 0,
        ]);

        if ($listing->isDirty('name')) {
            $listing->slug = ProductSeoHelper::uniqueSlug((string) $listing->name, (int) $listing->id);
        }

        $meta = ProductSeoHelper::resolve(array_merge($listing->toArray(), ['_auto_seo' => true]));
        $listing->meta_title = $meta['meta_title'];
        $listing->meta_description = $meta['meta_description'];
        $listing->meta_keywords = $meta['meta_keywords'];
        $listing->save();

        $this->syncImages($request, $listing);

        return redirect()
            ->route('admin.users.listings.index', $user)
            ->with('success', 'Listing updated.');
    }

    public function destroy(User $user, Product $listing): RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }
        if ($err = $this->assertListing($user, $listing)) {
            return $err;
        }

        $listing->status = 'removed';
        $listing->save();
        $listing->delete();

        return redirect()
            ->route('admin.users.listings.index', $user)
            ->with('success', 'Listing removed (soft-deleted).');
    }

    public function restore(User $user, int $listingId): RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }

        $listing = Product::withTrashed()
            ->where('seller_type', 'private')
            ->where('seller_id', $user->id)
            ->findOrFail($listingId);

        $listing->restore();
        if ($listing->status === 'removed') {
            $listing->update(['status' => 'draft']);
        }

        return redirect()
            ->route('admin.users.listings.index', $user)
            ->with('success', 'Listing restored.');
    }

    public function updateStatus(Request $request, User $user, Product $listing): RedirectResponse
    {
        if ($redirect = CustomerAdminService::ensureCustomer($user)) {
            return $redirect;
        }
        if ($err = $this->assertListing($user, $listing)) {
            return $err;
        }

        $request->validate([
            'status' => 'required|in:draft,pending,published,rejected,unpublished',
        ]);

        $listing->update(['status' => $request->status]);

        return redirect()
            ->route('admin.users.listings.index', $user)
            ->with('success', 'Listing status updated to ' . $request->status . '.');
    }

    private function assertListing(User $user, Product $listing): ?RedirectResponse
    {
        if ($listing->seller_type !== 'private' || (int) $listing->seller_id !== (int) $user->id) {
            return redirect()
                ->route('admin.users.listings.index', $user)
                ->with('error', 'Listing not found for this customer.');
        }

        return null;
    }

    private function syncImages(Request $request, Product $product): void
    {
        $thumbnailFile = $request->file('thumbnail');
        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $product->update([
                'thumbnail_path' => UploadHelper::storePublic($thumbnailFile, 'products/' . $product->id),
            ]);
        }

        if ($request->hasFile('images')) {
            $product->media()->delete();
            foreach ($request->file('images') as $i => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $path = UploadHelper::storePublic($file, 'products/' . $product->id);
                ProductMedia::create([
                    'product_id' => $product->id,
                    'type' => 'image',
                    'path' => $path,
                    'sort_order' => $i,
                ]);
            }
            $firstMedia = $product->media()->orderBy('sort_order')->first();
            if ($firstMedia && ! $product->thumbnail_path) {
                $product->update(['thumbnail_path' => $firstMedia->path]);
            }
        }
    }

    private function uniquePrivateSku(): string
    {
        do {
            $sku = 'PVT-' . strtoupper(Str::random(10));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
