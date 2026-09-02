<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:product,store',
            'reviewable_id' => 'required|integer',
        ]);

        $type = $request->reviewable_type === 'product' ? Product::class : Store::class;
        $reviewable = $type::find($request->reviewable_id);
        if (!$reviewable) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $query = Review::where('reviewable_type', $type)
            ->where('reviewable_id', $reviewable->id)
            ->approved()
            ->with(['user:id,name', 'media', 'reply.user:id,name'])
            ->orderByDesc('created_at');

        $userId = optional($request->user('sanctum'))->id ?? auth('sanctum')->id();
        if ($userId) {
            $query->withExists([
                'helpfulUsers as is_helpful' => fn ($q) => $q->where('user_id', $userId),
            ]);
        }

        $reviews = $query->paginate($request->get('per_page', 10));
        $stats = $this->getRatingStats($reviewable);

        $items = collect($reviews->items())->map(function (Review $r) use ($userId) {
            $arr = $r->toArray();
            $arr['is_helpful'] = $userId ? (bool) ($r->is_helpful ?? false) : false;
            return $arr;
        })->values()->all();

        return response()->json([
            'success' => true,
            'reviews' => $items,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'nullable|in:product,store',
            'reviewable_id' => 'nullable|integer',
            'product_ids' => 'nullable|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'order_id' => 'nullable|integer|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:5000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        // Batch: same rating/body for selected products from a delivered order
        $productIds = collect($request->input('product_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isNotEmpty() || ($request->input('reviewable_type') === 'product' && $request->filled('reviewable_id'))) {
            if ($productIds->isEmpty() && $request->filled('reviewable_id')) {
                $productIds = collect([(int) $request->reviewable_id]);
            }

            $order = null;
            if ($request->filled('order_id')) {
                $order = Order::where('id', $request->order_id)
                    ->where('user_id', $user->id)
                    ->with('items')
                    ->first();
                if (!$order) {
                    return response()->json(['success' => false, 'message' => 'Order not found'], 404);
                }
                $orderStatus = $order->effective_status ?? $order->status;
                if (!in_array($orderStatus, ['completed'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only review products after the order is completed.',
                    ], 422);
                }
                $orderProductIds = $order->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique();
                $invalid = $productIds->diff($orderProductIds);
                if ($invalid->isNotEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more products are not part of this order.',
                    ], 422);
                }
            }

            $created = [];
            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }

                $isVerified = $order
                    ? true
                    : $this->checkVerifiedPurchase($user->id, 'product', $productId);

                if (!$isVerified) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only review products from delivered orders. Open My Orders → order detail to leave a review.',
                    ], 422);
                }

                $existing = Review::where('user_id', $user->id)
                    ->where('reviewable_type', Product::class)
                    ->where('reviewable_id', $product->id)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You already reviewed this product',
                    ], 422);
                }

                $review = Review::create([
                    'user_id' => $user->id,
                    'reviewable_type' => Product::class,
                    'reviewable_id' => $product->id,
                    'rating' => $request->rating,
                    'title' => $request->title,
                    'body' => $request->body,
                    'is_verified_purchase' => true,
                    'status' => 'pending',
                ]);

                if ($request->hasFile('images') && count($productIds) === 1) {
                    $review->media()->delete();
                    foreach ($request->file('images') as $i => $file) {
                        $path = \App\Support\UploadHelper::storePublic($file, 'reviews/' . $review->id);
                        $review->media()->create(['path' => $path, 'sort_order' => $i]);
                    }
                }

                $created[] = $review->load('user', 'media');
            }

            if (empty($created)) {
                return response()->json(['success' => false, 'message' => 'No products to review'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => count($created) > 1
                    ? 'Reviews submitted. They will appear after moderation.'
                    : 'Review submitted. It will appear after moderation.',
                'reviews' => $created,
                'review' => $created[0],
            ], 201);
        }

        // Store reviews (unchanged single path)
        $request->validate([
            'reviewable_type' => 'required|in:store',
            'reviewable_id' => 'required|integer',
        ]);

        $type = Store::class;
        $reviewable = Store::find($request->reviewable_id);
        if (!$reviewable) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $isVerified = $this->checkVerifiedPurchase($user->id, 'store', $request->reviewable_id);

        $existing = Review::where('user_id', $user->id)
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $reviewable->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already reviewed this product',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => $type,
            'reviewable_id' => $reviewable->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'body' => $request->body,
            'is_verified_purchase' => $isVerified,
            'status' => 'pending',
        ]);

        if ($request->hasFile('images')) {
            $review->media()->delete();
            foreach ($request->file('images') as $i => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'reviews/' . $review->id);
                $review->media()->create(['path' => $path, 'sort_order' => $i]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Review submitted. It will appear after moderation.',
            'review' => $review->load('user', 'media'),
        ], 201);
    }

    public function helpful(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $exists = $review->helpfulUsers()->where('user_id', $user->id)->exists();
        if ($exists) {
            $review->helpfulUsers()->detach($user->id);
            $review->decrement('helpful_count');
            $helpful = false;
        } else {
            $review->helpfulUsers()->attach($user->id);
            $review->increment('helpful_count');
            $helpful = true;
        }

        return response()->json([
            'success' => true,
            'helpful' => $helpful,
            'helpful_count' => $review->fresh()->helpful_count,
        ]);
    }

    public function report(Request $request, Review $review): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $exists = \DB::table('review_reports')->where('review_id', $review->id)->where('user_id', $user->id)->exists();
        if (!$exists) {
            \DB::table('review_reports')->insert([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $review->increment('reported_count');
        }

        return response()->json(['success' => true, 'message' => 'Review reported.']);
    }

    public function reply(Request $request, Review $review): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $canReply = false;
        if ($review->reviewable_type === Product::class) {
            $product = $review->reviewable;
            $store = $product?->store;
            $canReply = $store && $store->seller && $store->seller->user_id === $user->id;
            // Private seller (C2C): product.seller_id matches user
            if (!$canReply && ($product?->seller_type === 'private') && (int) $product->seller_id === (int) $user->id) {
                $canReply = true;
            }
        } elseif ($review->reviewable_type === Store::class) {
            $store = $review->reviewable;
            $canReply = $store && $store->seller && $store->seller->user_id === $user->id;
        }

        if (!$canReply) {
            return response()->json(['success' => false, 'message' => 'Only the seller can reply'], 403);
        }

        $existingReply = ReviewReply::where('review_id', $review->id)->first();
        if ($existingReply) {
            return response()->json([
                'success' => false,
                'message' => 'Reply already exists and cannot be edited',
            ], 422);
        }

        ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply posted',
            'reply' => $review->fresh()->replies()->with('user:id,name')->first(),
        ]);
    }

    protected function checkVerifiedPurchase(int $userId, string $type, int $id): bool
    {
        // Only completed orders qualify for product/store reviews (Task 42)
        $doneStatuses = ['completed'];

        if ($type === 'product') {
            return Order::where('user_id', $userId)
                ->where(function ($q) use ($doneStatuses) {
                    $q->whereIn('status', $doneStatuses);
                })
                ->whereHas('items', fn ($q) => $q->where('product_id', $id))
                ->get()
                ->contains(function (Order $order) use ($doneStatuses) {
                    $status = $order->effective_status ?? $order->status;
                    return in_array($status, $doneStatuses, true);
                });
        }
        if ($type === 'store') {
            return Order::where('user_id', $userId)
                ->whereHas('items', fn ($q) => $q->where('store_id', $id))
                ->get()
                ->contains(function (Order $order) use ($doneStatuses) {
                    $status = $order->effective_status ?? $order->status;
                    return in_array($status, $doneStatuses, true);
                });
        }
        return false;
    }

    protected function getRatingStats($reviewable): array
    {
        $reviews = Review::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->approved();

        $total = $reviews->count();
        $avg = $total > 0 ? round($reviews->avg('rating'), 2) : 0;
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($reviews->selectRaw('rating, count(*) as c')->groupBy('rating')->get() as $r) {
            $distribution[(int) $r->rating] = (int) $r->c;
        }

        return ['total' => $total, 'average' => $avg, 'distribution' => $distribution];
    }
}
