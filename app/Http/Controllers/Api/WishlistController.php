<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    private function getIdentifier(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            return ['user_id' => $user->id, 'session_id' => null];
        }
        $sessionId = $request->header('X-Session-ID') ?: $request->cookie('wishlist_session') ?: 'guest-' . uniqid();
        return ['user_id' => null, 'session_id' => $sessionId];
    }

    public function index(Request $request): JsonResponse
    {
        $ids = $this->getIdentifier($request);
        $items = Wishlist::when($ids['user_id'], fn ($q) => $q->where('user_id', $ids['user_id']))
            ->when($ids['session_id'], fn ($q) => $q->where('session_id', $ids['session_id']))
            ->with(['product' => fn ($q) => $q->with(['media', 'category', 'store', 'sellerUser'])])
            ->orderByDesc('created_at')
            ->get();

        // Only keep products that are still available (published, in stock, not expired).
        $items = $items->filter(function ($w) {
            $p = $w->product;
            if (! $p) {
                return false;
            }
            if ($p->status !== 'published') {
                return false;
            }
            if ($p->expires_at && $p->expires_at->isPast()) {
                return false;
            }
            if ($p->isOutOfStock()) {
                return false;
            }

            return true;
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items->map(fn ($w) => [
                'id' => $w->id,
                'product_id' => $w->product_id,
                'product' => $this->formatProduct($w->product),
                'price_alert' => $w->price_alert,
                'stock_alert' => $w->stock_alert,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $ids = $this->getIdentifier($request);

        $existing = Wishlist::when($ids['user_id'], fn ($q) => $q->where('user_id', $ids['user_id']))
            ->when($ids['session_id'], fn ($q) => $q->where('session_id', $ids['session_id']))
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            return response()->json(['success' => true, 'message' => 'Already in wishlist', 'wishlist' => $existing]);
        }

        $wishlist = Wishlist::create([
            'user_id' => $ids['user_id'],
            'session_id' => $ids['session_id'],
            'product_id' => $request->product_id,
        ]);

        $product = Product::with(['media', 'category', 'store'])->find($request->product_id);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist',
            'wishlist' => [
                'id' => $wishlist->id,
                'product_id' => $wishlist->product_id,
                'product' => $this->formatProduct($product),
            ],
        ], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $ids = $this->getIdentifier($request);
        Wishlist::when($ids['user_id'], fn ($q) => $q->where('user_id', $ids['user_id']))
            ->when($ids['session_id'], fn ($q) => $q->where('session_id', $ids['session_id']))
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Removed from wishlist']);
    }

    public function moveToCart(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $wishlist = Wishlist::where('user_id', $user->id)->where('product_id', $productId)->first();
        if (!$wishlist) {
            return response()->json(['success' => false, 'message' => 'Not in wishlist'], 404);
        }

        $product = Product::published()->with(['media', 'store'])->find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not available'], 404);
        }

        $cart = \App\Models\Cart::getOrCreate($user->id);
        $existing = $cart->items()->where('product_id', $productId)->first();
        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + 1]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'quantity' => 1,
                'price' => $product->price,
            ]);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Moved to cart',
            'cart_count' => (int) $cart->items()->sum('quantity'),
        ]);
    }

    public function toggleAlert(Request $request, int $productId): JsonResponse
    {
        $request->validate(['type' => 'required|in:price_alert,stock_alert', 'enabled' => 'boolean']);
        $ids = $this->getIdentifier($request);

        $wishlist = Wishlist::when($ids['user_id'], fn ($q) => $q->where('user_id', $ids['user_id']))
            ->when($ids['session_id'], fn ($q) => $q->where('session_id', $ids['session_id']))
            ->where('product_id', $productId)
            ->first();

        if (!$wishlist) {
            return response()->json(['success' => false, 'message' => 'Not in wishlist'], 404);
        }

        $wishlist->update([$request->type => $request->boolean('enabled', true)]);

        return response()->json(['success' => true, 'wishlist' => $wishlist]);
    }

    private function formatProduct(?Product $p): ?array
    {
        if (!$p) return null;
        $store = $p->store;
        $vendor = $store?->name ?? $p->sellerUser?->name ?? '—';
        $available = $p->getAvailableQuantity();
        $expired = $p->expires_at && $p->expires_at->isPast();

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->name,
            'name' => $p->name,
            'price' => (float) $p->price,
            'originalPrice' => $p->compare_at_price ? (float) $p->compare_at_price : null,
            'image' => $p->getMainImageUrl(),
            'vendor' => $vendor,
            'status' => $p->status,
            'available_quantity' => $available,
            'in_stock' => ! $p->isOutOfStock(),
            'expires_at' => $p->expires_at?->toIso8601String(),
            'is_expired' => (bool) $expired,
        ];
    }
}
