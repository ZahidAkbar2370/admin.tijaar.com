<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReservedStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private const RESERVE_TTL_MINUTES = 60;

    private static function variantIdFromRequest(Request $request): int
    {
        $v = $request->input('variant_id');
        return (is_numeric($v) && (int) $v > 0) ? (int) $v : 0;
    }

    /**
     * Drop all cart reservations then re-hold for every remaining line.
     * Avoids wiping sibling lines when one item is updated/removed.
     */
    private function syncCartReservations(Cart $cart): void
    {
        ReservedStock::releaseFor('cart', (string) $cart->id);
        $cart->loadMissing('items');
        foreach ($cart->items as $item) {
            $qty = (int) $item->quantity;
            if ($qty > 0) {
                ReservedStock::reserve((int) $item->product_id, $qty, 'cart', (string) $cart->id, self::RESERVE_TTL_MINUTES);
            }
        }
    }

    private function availableForCart(Product $product, Cart $cart, int $variantId = 0, ?ProductVariant $variant = null): int
    {
        if ($variantId > 0 && $variant) {
            return (int) $variant->quantity;
        }
        $refType = $cart->user_id ? 'cart' : 'cart_guest';
        $refId = $cart->user_id ? (string) $cart->id : (string) ($cart->session_id ?? $cart->id);
        return (int) $product->getAvailableQuantity($refType, $refId);
    }

    private function cartResponse(Cart $cart): array
    {
        $cart->load([
            'items.product.media',
            'items.product.category',
            'items.product.store.seller',
            'items.product.sellerUser',
            'items.variant',
        ]);
        $items = $cart->items->map(function ($i) use ($cart) {
            $product = $i->product;
            $variant = $i->variant_id > 0 ? $i->variant : null;
            $attrs = $i->options ?? ($variant ? $variant->attributes ?? [] : []);
            $label = is_array($attrs) && !empty($attrs)
                ? implode(', ', array_map(fn ($k, $v) => ucfirst($k) . ': ' . $v, array_keys($attrs), $attrs))
                : '';
            // Variant image: image_path or first of image_paths (so cart shows selected variant's image)
            $variantImagePath = null;
            if ($variant) {
                $variantImagePath = $variant->image_path
                    ?? (is_array($variant->image_paths ?? null) && !empty($variant->image_paths) ? $variant->image_paths[0] : null);
            }
            $image = $variantImagePath
                ? \App\Support\UploadHelper::url($variantImagePath)
                : $product->getMainImageUrl();

            $store = $product->store;
            $sellerUser = $product->sellerUser;
            $sellerType = $product->seller_type ?? 'business';
            $verified = false;
            if ($sellerType === 'private') {
                $verified = $sellerUser
                    && ($sellerUser->is_private_seller ?? false)
                    && ($sellerUser->private_seller_kyc_status ?? '') === 'approved';
            } else {
                $seller = $store?->seller ?? $sellerUser?->seller;
                $verified = $seller
                    && $seller->status === 'approved'
                    && ($seller->kyc_status ?? '') === 'verified';
            }
            $storeLogo = $store?->logo
                ? (\App\Support\UploadHelper::deliveryUrl($store->logo, 64, 85) ?? \App\Support\UploadHelper::url($store->logo))
                : null;

            $refType = $cart->user_id ? 'cart' : 'cart_guest';
            $refId = $cart->user_id ? (string) $cart->id : (string) ($cart->session_id ?? $cart->id);
            if ($variant && $product->track_inventory !== false) {
                $lineAvailable = (int) $variant->quantity;
            } elseif ($product->track_inventory !== false) {
                $lineAvailable = (int) $product->getAvailableQuantity($refType, $refId);
            } else {
                $lineAvailable = (int) $product->getEffectiveQuantity();
            }

            return [
                'id' => $i->product_id,
                'product_id' => $i->product_id,
                'cart_item_id' => $i->id,
                'variant_id' => $i->variant_id > 0 ? $i->variant_id : null,
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'price' => (float) $variant->price,
                    'attributes' => $variant->attributes ?? [],
                    'image' => $variantImagePath ? \App\Support\UploadHelper::url($variantImagePath) : null,
                    'image_url' => $variantImagePath ? \App\Support\UploadHelper::url($variantImagePath) : null,
                ] : null,
                'flash_deal_id' => $i->flash_deal_id ? (int) $i->flash_deal_id : null,
                'slug' => $product->slug,
                'title' => $product->name,
                'name' => $product->name,
                'price' => (float) $i->price,
                'image' => $image,
                'image_url' => $image,
                'variant_label' => $label,
                'category' => $product->category?->name,
                'categorySlug' => $product->category?->slug,
                'vendor' => $store?->name ?? $sellerUser?->name ?? '—',
                'vendor_slug' => $store?->slug ?? null,
                'vendor_logo' => $storeLogo,
                'vendor_logo_alt' => $store?->logo_alt,
                'vendor_city' => \App\Services\SellerOriginResolver::forProduct($product) ?: null,
                'verified' => $verified,
                'seller_type' => $sellerType,
                'store_id' => $product->store_id ? (int) $product->store_id : null,
                'seller_id' => $product->seller_id ? (int) $product->seller_id : null,
                'shipping_mode' => $product->shipping_mode ?? 'customer_pays',
                'shipping_cost_cached' => $product->shipping_cost_cached !== null
                    ? (float) $product->shipping_cost_cached
                    : null,
                'quantity' => $i->quantity,
                'available_quantity' => $lineAvailable,
                'stock_status' => $product->track_inventory === false
                    ? 'in_stock'
                    : ($lineAvailable > 0 ? 'in_stock' : 'out_of_stock'),
                'track_inventory' => $product->track_inventory !== false,
                'variants' => $attrs,
            ];
        });
        return [
            'success' => true,
            'cart' => [
                'items' => $items,
                'subtotal' => (float) $cart->subtotal,
            ],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => true, 'cart' => ['items' => [], 'subtotal' => 0]]);
        }
        $cart = Cart::getOrCreate($user->id, null);
        return response()->json($this->cartResponse($cart));
    }

    /**
     * Guest cart: get by session_id (no auth).
     */
    public function guestIndex(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|string|max:255']);
        $cart = Cart::getOrCreate(null, $request->session_id);
        return response()->json($this->cartResponse($cart));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'set_quantity' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login to add to cart'], 401);
        }
        if (($user->role ?? '') === 'seller') {
            return response()->json([
                'success' => false,
                'message' => 'Seller accounts can browse products but cannot add items to cart or place orders.',
                'error_code' => 'seller_cannot_shop',
            ], 403);
        }

        $resolved = $this->resolveProductForCart((int) $request->product_id);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        $product = $resolved;

        $variantId = self::variantIdFromRequest($request);
        $options = $request->input('options', []);

        // Prevent customer from buying their own listings
        $sellerUserId = $product->store_id && $product->store?->seller
            ? (int) $product->store->seller->user_id
            : ($product->seller_id ? (int) $product->seller_id : null);
        if ($sellerUserId && $sellerUserId === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot add your own listing to the cart. Log in as a different customer to purchase it.',
            ], 422);
        }

        $price = (float) $product->price;
        $variant = null;

        if ($variantId > 0) {
            $variant = ProductVariant::where('product_id', $product->id)->where('id', $variantId)->first();
            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Invalid variant'], 422);
            }
            $price = (float) $variant->price;
            if (empty($options) && is_array($variant->attributes)) {
                $options = $variant->attributes;
            }
        }

        $cart = Cart::getOrCreate($user->id, null);
        $available = $this->availableForCart($product, $cart, $variantId, $variant);

        if ($available <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This product is out of stock and cannot be added to your cart.',
                'code' => 'out_of_stock',
            ], 422);
        }

        $qty = (int) $request->quantity;
        if ($qty > $available) {
            return response()->json([
                'success' => false,
                'message' => $available === 1
                    ? 'Only 1 unit is available for this product.'
                    : "Only {$available} units are available for this product.",
                'code' => 'insufficient_stock',
                'available' => $available,
            ], 422);
        }

        if ($cart->items()->whereNotNull('flash_deal_id')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a flash deal in your cart. Complete your purchase first.',
            ], 422);
        }
        $existing = $cart->items()->where('product_id', $product->id)->where('variant_id', $variantId)->first();
        $setQuantity = $request->boolean('set_quantity');
        if ($existing) {
            $newQty = $setQuantity ? $qty : $existing->quantity + $qty;
            if ($newQty > $available) {
                return response()->json([
                    'success' => false,
                    'message' => $available === 1
                        ? 'Only 1 unit is available for this product.'
                        : "Only {$available} units are available for this product.",
                    'code' => 'insufficient_stock',
                    'available' => $available,
                ], 422);
            }
            $existing->update(['quantity' => $newQty]);
            $this->syncCartReservations($cart->fresh());
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'price' => $price,
                'options' => !empty($options) ? $options : null,
            ]);
            ReservedStock::reserve($product->id, $qty, 'cart', (string) $cart->id, self::RESERVE_TTL_MINUTES);
        }

        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Guest cart: add (no auth). Uses session_id. Supports variant_id so variant price, image, and options are stored.
     */
    public function guestAdd(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'variant_id' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
        ]);

        $resolved = $this->resolveProductForCart((int) $request->product_id, false);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        $product = $resolved;
        $variantId = self::variantIdFromRequest($request);
        $options = $request->input('options', []);

        $price = (float) $product->price;
        $variant = null;

        if ($variantId > 0) {
            $variant = ProductVariant::where('product_id', $product->id)->where('id', $variantId)->first();
            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Invalid variant'], 422);
            }
            $price = (float) $variant->price;
            if (empty($options) && is_array($variant->attributes)) {
                $options = $variant->attributes;
            }
        }

        $cart = Cart::getOrCreate(null, $request->session_id);
        $available = $this->availableForCart($product, $cart, $variantId, $variant);

        if ($available <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This product is out of stock and cannot be added to your cart.',
                'code' => 'out_of_stock',
            ], 422);
        }

        $qty = (int) $request->quantity;
        if ($qty > $available) {
            return response()->json([
                'success' => false,
                'message' => $available === 1
                    ? 'Only 1 unit is available for this product.'
                    : "Only {$available} units are available for this product.",
                'code' => 'insufficient_stock',
                'available' => $available,
            ], 422);
        }

        $existing = $cart->items()->where('product_id', $product->id)->where('variant_id', $variantId)->first();
        if ($existing) {
            $newQty = $existing->quantity + $qty;
            if ($newQty > $available) {
                return response()->json([
                    'success' => false,
                    'message' => $available === 1
                        ? 'Only 1 unit is available for this product.'
                        : "Only {$available} units are available for this product.",
                    'code' => 'insufficient_stock',
                    'available' => $available,
                ], 422);
            }
            $existing->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'price' => $price,
                'options' => !empty($options) ? $options : null,
            ]);
        }
        ReservedStock::reserve($product->id, $qty, 'cart_guest', $request->session_id, self::RESERVE_TTL_MINUTES);

        return response()->json($this->cartResponse($cart->fresh()));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'variant_id' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $variantId = self::variantIdFromRequest($request);
        $cart = Cart::getOrCreate($user->id, null);
        $item = $cart->items()->where('product_id', $request->product_id)->where('variant_id', $variantId)->firstOrFail();
        if ($item->flash_deal_id) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity cannot be changed for items that are part of a flash deal. Remove the deal from cart to add products separately.',
            ], 422);
        }
        $product = $item->product;
        $available = $this->availableForCart(
            $product,
            $cart,
            $variantId,
            $item->variant_id > 0 ? $item->variant : null
        );

        if ($request->quantity <= 0) {
            $item->delete();
            $this->syncCartReservations($cart->fresh());
        } else {
            if ($request->quantity > $available) {
                return response()->json([
                    'success' => false,
                    'message' => $available === 1
                        ? 'Only 1 unit is available for this product.'
                        : "Only {$available} units are available for this product.",
                    'code' => 'insufficient_stock',
                    'available' => $available,
                ], 422);
            }
            $item->update(['quantity' => $request->quantity]);
            $this->syncCartReservations($cart->fresh());
        }

        return response()->json($this->cartResponse($cart->fresh()));
    }

    public function remove(Request $request, int $productId): JsonResponse
    {
        $request->validate(['variant_id' => 'nullable|integer|min:0']);
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $variantId = self::variantIdFromRequest($request);
        $cart = Cart::getOrCreate($user->id, null);
        $cart->items()->where('product_id', $productId)->where('variant_id', $variantId)->delete();
        $this->syncCartReservations($cart->fresh());
        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Add an entire flash deal to cart: all products with discounted prices applied.
     */
    public function addDeal(Request $request): JsonResponse
    {
        $request->validate(['flash_deal_id' => 'required|integer|exists:flash_deals,id']);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login to add this deal to cart'], 401);
        }
        if (($user->role ?? '') === 'seller') {
            return response()->json([
                'success' => false,
                'message' => 'Seller accounts can browse products but cannot add items to cart or place orders.',
                'error_code' => 'seller_cannot_shop',
            ], 403);
        }

        $deal = FlashDeal::active()
            ->notExpired()
            ->with(['store.seller', 'products' => fn ($q) => $q->where('products.status', 'published')->with(['media', 'variants'])])
            ->find($request->flash_deal_id);

        if (!$deal || $deal->products->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Deal not found or no products'], 404);
        }

        $store = $deal->store;
        $sellerUserId = $store && $store->seller ? (int) $store->seller->user_id : null;
        if ($sellerUserId && $sellerUserId === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot add your own deal to the cart.',
            ], 422);
        }

        // Resolve price and variant per product (use pivot variant_id when set)
        $productPrices = [];
        foreach ($deal->products as $p) {
            $pivot = $p->pivot;
            $variantId = $pivot && $pivot->variant_id ? (int) $pivot->variant_id : 0;
            $price = (float) $p->price;
            $attrs = null;
            if ($variantId > 0 && $p->relationLoaded('variants')) {
                $variant = $p->variants->firstWhere('id', $variantId);
                if ($variant) {
                    $price = (float) $variant->price;
                    $attrs = is_array($variant->attributes) ? $variant->attributes : null;
                }
            }
            $productPrices[$p->id] = ['price' => $price, 'variant_id' => $variantId > 0 ? $variantId : 0, 'options' => $attrs];
        }

        $totalOriginal = array_sum(array_column($productPrices, 'price'));
        if ($totalOriginal <= 0) {
            return response()->json(['success' => false, 'message' => 'Deal has no valid products'], 422);
        }

        $discountAmount = $deal->discount_type === 'percentage'
            ? $totalOriginal * ((float) $deal->discount_value / 100)
            : min((float) $deal->discount_value, $totalOriginal);
        $totalAfterDiscount = max(0, $totalOriginal - $discountAmount);
        $ratio = $totalAfterDiscount / $totalOriginal;

        $cart = Cart::getOrCreate($user->id, null);
        $productIds = $deal->products->pluck('id')->toArray();

        // Remove any existing cart items for these products so we add them once with deal price
        $cart->items()->whereIn('product_id', $productIds)->get()->each(function ($item) use ($cart) {
            ReservedStock::releaseFor('cart', (string) $cart->id);
        });
        $cart->items()->whereIn('product_id', $productIds)->delete();

        foreach ($deal->products as $product) {
            $info = $productPrices[$product->id] ?? ['price' => (float) $product->price, 'variant_id' => 0, 'options' => null];
            $available = $info['variant_id'] > 0
                ? (int) (ProductVariant::where('product_id', $product->id)->where('id', $info['variant_id'])->value('quantity') ?? 0)
                : (int) $product->getAvailableQuantity();
            if ($available < 1) {
                continue;
            }
            $itemPrice = round($info['price'] * $ratio, 2);
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $info['variant_id'],
                'flash_deal_id' => $deal->id,
                'quantity' => 1,
                'price' => $itemPrice,
                'options' => $info['options'],
            ]);
            ReservedStock::reserve($product->id, 1, 'cart', (string) $cart->id, self::RESERVE_TTL_MINUTES);
        }

        if ($cart->items()->whereIn('product_id', $productIds)->count() === 0) {
            return response()->json(['success' => false, 'message' => 'No products from this deal are available'], 422);
        }

        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Guest cart: update quantity (no auth). Use variant_id to target the correct line when product has variants.
     */
    public function guestUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'variant_id' => 'nullable|integer|min:0',
        ]);
        $variantId = self::variantIdFromRequest($request);
        $cart = Cart::getOrCreate(null, $request->session_id);
        $item = $cart->items()->where('product_id', $request->product_id)->where('variant_id', $variantId)->firstOrFail();
        if ($request->quantity <= 0) {
            ReservedStock::releaseFor('cart_guest', $request->session_id);
            $item->delete();
        } else {
            $available = $item->variant_id > 0 && $item->variant
                ? (int) $item->variant->quantity
                : $item->product->getAvailableQuantity();
            if ($request->quantity > $available) {
                return response()->json(['success' => false, 'message' => "Only {$available} available"], 422);
            }
            $item->update(['quantity' => $request->quantity]);
        }
        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Guest cart: remove item (no auth). Pass variant_id in query to remove the correct line when product has variants.
     */
    public function guestRemove(Request $request, int $productId): JsonResponse
    {
        $request->validate(['session_id' => 'required|string|max:255']);
        $variantId = self::variantIdFromRequest($request);
        $cart = Cart::getOrCreate(null, $request->query('session_id'));
        $cart->items()->where('product_id', $productId)->where('variant_id', $variantId)->delete();
        ReservedStock::releaseFor('cart_guest', $request->query('session_id'));
        return response()->json($this->cartResponse($cart->fresh()));
    }

    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'session_id' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $cart = Cart::getOrCreate($user->id, null);
        $sessionId = $request->session_id;

        foreach ($request->items as $row) {
            $product = Product::published()->with('store.seller')->find($row['product_id']);
            if (!$product) continue;
            $sellerUserId = $product->store_id && $product->store?->seller
                ? (int) $product->store->seller->user_id
                : ($product->seller_id ? (int) $product->seller_id : null);
            if ($sellerUserId && $sellerUserId === (int) $user->id) {
                continue; // Skip own listings when merging guest cart
            }
            $qty = (int) $row['quantity'];
            $variantId = (int) ($row['variant_id'] ?? 0);
            $price = (float) $product->price;
            if ($variantId > 0) {
                $variant = ProductVariant::where('product_id', $product->id)->where('id', $variantId)->first();
                if ($variant) {
                    $price = (float) $variant->price;
                    $available = (int) $variant->quantity;
                } else {
                    $variantId = 0;
                    $available = $product->getAvailableQuantity();
                }
            } else {
                $available = $product->getAvailableQuantity();
            }
            $qty = min($qty, $available);
            if ($qty < 1) continue;

            $existing = $cart->items()->where('product_id', $product->id)->where('variant_id', $variantId)->first();
            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $qty]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'quantity' => $qty,
                    'price' => $price,
                    'options' => isset($row['options']) && is_array($row['options']) ? $row['options'] : null,
                ]);
            }
            ReservedStock::reserve($product->id, $qty, 'cart', (string) $cart->id, self::RESERVE_TTL_MINUTES);
        }

        if ($sessionId) {
            ReservedStock::releaseFor('cart_guest', $sessionId);
            $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();
            if ($guestCart) {
                $guestCart->items()->delete();
            }
        }

        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Clear all items from the authenticated user's cart and release reserved stock.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $cart = Cart::getOrCreate($user->id, null);
        ReservedStock::releaseFor('cart', (string) $cart->id);
        $cart->items()->delete();
        return response()->json($this->cartResponse($cart->fresh()));
    }

    /**
     * Resolve a product that can be added to cart, with clear messages when not purchasable.
     *
     * @return Product|JsonResponse
     */
    private function resolveProductForCart(int $productId, bool $withStore = true): Product|JsonResponse
    {
        $query = Product::query()->with('variants');
        if ($withStore) {
            $query->with('store.seller');
        }
        $product = $query->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'This product is no longer available.',
                'code' => 'not_found',
            ], 404);
        }

        $status = (string) ($product->status ?? '');
        if ($status !== 'published') {
            $messages = [
                'pending' => 'This product is pending approval and cannot be purchased yet.',
                'draft' => 'This product is not listed for sale yet.',
                'unpublished' => 'This product is currently inactive or out of stock and cannot be added to cart.',
                'rejected' => 'This product is not available for purchase.',
            ];
            return response()->json([
                'success' => false,
                'message' => $messages[$status] ?? 'This product is not available for purchase right now.',
                'code' => 'not_published',
                'status' => $status,
            ], 422);
        }

        return $product;
    }
}
