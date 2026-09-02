<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Dispute::with('order:id,order_number');

        if ($user->role === 'seller') {
            $orderIds = \App\Models\Order::where(function ($q) use ($user) {
                $q->whereHas('items.store.seller', fn ($sq) => $sq->where('user_id', $user->id))
                    ->orWhereHas('items', fn ($sq) => $sq->where('seller_id', $user->id));
            })->pluck('id');
            $query->whereIn('order_id', $orderIds);
        } elseif ($user->role === 'customer') {
            // Buyer disputes OR disputes on orders where this customer is the private listing seller
            $sellerOrderIds = \App\Models\OrderItem::where('seller_id', $user->id)
                ->where('seller_type', 'private')
                ->distinct()
                ->pluck('order_id');
            $query->where(function ($q) use ($user, $sellerOrderIds) {
                $q->where('user_id', $user->id);
                if ($sellerOrderIds->isNotEmpty()) {
                    $q->orWhereIn('order_id', $sellerOrderIds);
                }
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $disputes = $query
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'disputes' => $disputes->items(),
            'pagination' => ['current_page' => $disputes->currentPage(), 'last_page' => $disputes->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|in:return,refund',
            'reason' => 'nullable|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($request->order_id);

        if ($order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $existing = Dispute::where('order_id', $order->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Dispute already exists for this order'], 422);
        }

        $dispute = Dispute::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => $request->type,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'body' => $request->description,
            'is_admin' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispute opened',
            'dispute' => $dispute->load('order'),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dispute = Dispute::with(['order', 'messages.user:id,name'])
            ->findOrFail($id);

        $canView = $dispute->user_id === $user->id;
        if (!$canView && $user->role === 'seller') {
            $orderHasSellerItems = $dispute->order && $dispute->order->items()
                ->where(function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                    if ($user->seller?->store) {
                        $q->orWhere('store_id', $user->seller->store->id);
                    }
                })
                ->exists();
            $canView = $orderHasSellerItems;
        }
        if (!$canView && $user->role === 'customer') {
            $canView = $dispute->order && $dispute->order->items()
                ->where('seller_id', $user->id)
                ->where('seller_type', 'private')
                ->exists();
        }
        if (!$canView) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'dispute' => $dispute,
        ]);
    }

    public function sellerRespond(Request $request, int $id): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:5000']);

        $dispute = Dispute::with('order')->findOrFail($id);
        $user = $request->user();

        $order = $dispute->order;
        $order->load('items.store.seller');

        $isBusinessSeller = $user->role === 'seller' && $order->items
            ->map(fn ($i) => $i->seller_id ?? $i->store?->seller?->user_id)
            ->filter()
            ->unique()
            ->contains($user->id);

        $isCustomerSeller = $user->role === 'customer' && $order->items
            ->where('seller_type', 'private')
            ->where('seller_id', $user->id)
            ->isNotEmpty();

        if (!$isBusinessSeller && !$isCustomerSeller) {
            return response()->json(['success' => false, 'message' => 'You are not the seller for this order'], 403);
        }

        if (!in_array($dispute->status, ['open'])) {
            return response()->json(['success' => false, 'message' => 'Dispute is closed'], 422);
        }

        $msg = DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'body' => $request->body,
            'is_admin' => false,
        ]);

        $dispute->update(['status' => 'seller_responded']);

        return response()->json(['success' => true, 'message' => $msg], 201);
    }

    public function addMessage(Request $request, int $id): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:5000']);

        $dispute = Dispute::with('order.items.store.seller')->findOrFail($id);
        $user = $request->user();
        if ($dispute->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if (!in_array($dispute->status, ['open', 'seller_responded'])) {
            return response()->json(['success' => false, 'message' => 'Dispute is closed'], 422);
        }

        $msg = DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'is_admin' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 201);
    }
}
