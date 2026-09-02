<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\WachatService;
use App\Support\CourierCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    /**
     * List shipments for seller's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $seller = $user->seller;
        if (!$seller?->store) {
            return response()->json(['success' => true, 'shipments' => [], 'pagination' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
        }

        $query = Shipment::whereHas('order', fn ($q) => $q->whereHas('items', fn ($i) => $i->where('store_id', $seller->store->id)))
            ->with('order:id,order_number,status')
            ->orderByDesc('created_at');

        $shipments = $query->paginate(20);
        $items = $shipments->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'order_id' => $s->order_id,
            'order_number' => $s->order?->order_number,
            'carrier' => $s->carrier,
            'tracking_number' => $s->tracking_number,
            'tracking_url' => $s->tracking_url
                ?: CourierCatalog::publicTrackingUrl((string) $s->carrier, $s->tracking_number),
            'status' => $s->status,
            'shipped_at' => $s->shipped_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'shipments' => $items,
            'pagination' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    /**
     * Add or update tracking for an order (seller).
     */
    public function addTracking(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'carrier' => 'required|string|max:50',
            'tracking_number' => 'required|string|max:100',
            'tracking_url' => 'nullable|string|max:500',
        ]);

        $carrier = \App\Support\CourierCatalog::normalize((string) $request->carrier);
        if (! \App\Support\CourierCatalog::isValid($carrier)) {
            $enabled = \App\Support\CourierCatalog::enabled();
            $labels = collect($enabled)->pluck('label')->implode(', ');

            return response()->json([
                'success' => false,
                'message' => $labels !== ''
                    ? 'Select an enabled courier: '.$labels.'.'
                    : 'No couriers are enabled. Ask admin to enable TCS, Leopard/LCS, or PostEx.',
            ], 422);
        }

        $trackingNumber = trim((string) $request->tracking_number);
        if ($trackingNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'Enter the tracking number printed on the courier receipt.',
            ], 422);
        }

        $user = $request->user();
        $seller = $user->seller;
        $store = $seller?->store;
        // Customer listings (seller_type=private) — any customer who owns items, not only KYC private sellers
        $isCustomerSeller = $user->role === 'customer';

        $order = null;
        $storeId = null;
        $sellerId = null;

        if ($store) {
            $order = Order::with('shipments')->whereHas('items', fn ($q) => $q->where('store_id', $store->id))->find($orderId);
            if ($order) {
                $storeId = $store->id;
            }
        }
        if (!$order && $isCustomerSeller) {
            $order = Order::with('shipments')->whereHas('items', fn ($q) => $q->where('seller_id', $user->id)->where('seller_type', 'private'))->find($orderId);
            if ($order) {
                $sellerId = $user->id;
            }
        }

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found or you do not have items in this order.'], 404);
        }

        // Seller must approve THEIR portion before adding tracking (multi-seller safe).
        $sellerItems = \App\Services\SellerFulfillmentService::sellerItems($order, $user);
        $portionShipment = $storeId
            ? $order->shipments->firstWhere('store_id', $storeId)
            : $order->shipments->first(fn ($s) => (int) $s->seller_id === (int) $sellerId && empty($s->store_id));
        $portionStatus = \App\Services\SellerFulfillmentService::portionStatus($sellerItems, $portionShipment);
        $sellerApproved = in_array($portionStatus, ['approved', 'shipped', 'delivered'], true);
        if (!$sellerApproved) {
            return response()->json([
                'success' => false,
                'message' => 'Approve your items before adding tracking.',
            ], 422);
        }

        if ($order->hasOpenReturnOrDispute()) {
            return response()->json(['success' => false, 'message' => 'Cannot add tracking: order has an open return/refund request or dispute.'], 422);
        }

        $shipment = $order->shipments()->firstOrNew(
            $storeId !== null ? ['store_id' => $storeId] : ['seller_id' => $sellerId]
        );
        $shipment->order_id = $order->id;
        $shipment->store_id = $storeId;
        $shipment->seller_id = $sellerId;
        $shipment->carrier = $carrier;
        $shipment->tracking_number = $trackingNumber;
        if ($carrier === 'tcs') {
            $shipment->tcs_cn_number = $trackingNumber;
            $shipment->lcs_cn_number = null;
        } elseif ($carrier === 'leopards') {
            $shipment->lcs_cn_number = $trackingNumber;
            $shipment->tcs_cn_number = null;
        } else {
            $shipment->tcs_cn_number = null;
            $shipment->lcs_cn_number = null;
        }
        $shipment->tracking_url = $request->filled('tracking_url')
            ? trim((string) $request->tracking_url)
            : \App\Support\CourierCatalog::publicTrackingUrl($carrier, $trackingNumber);
        $shipment->status = 'shipped';
        if (!$shipment->shipped_at) {
            $shipment->shipped_at = now();
        }
        $shipment->save();

        // Mark this seller's items as shipped
        if ($sellerItems->isNotEmpty()) {
            \App\Models\OrderItem::whereIn('id', $sellerItems->pluck('id'))
                ->whereNotIn('fulfillment_status', ['rejected', 'cancelled'])
                ->update(['fulfillment_status' => 'shipped']);
        }

        // Only sync tracking to order when this is a single-seller order (one store/seller in items).
        $order->load('items');
        $sellerStores = $order->items->pluck('store_id')->filter()->unique()->count();
        $sellerPrivate = $order->items->where('seller_type', 'private')->pluck('seller_id')->filter()->unique()->count();
        $singleSeller = ($sellerStores + $sellerPrivate) <= 1;
        $shipmentCount = $order->shipments()->count();
        if ($singleSeller && $shipmentCount === 1) {
            $order->update([
                'tracking_number' => $shipment->tracking_number,
                'tracking_url' => $shipment->tracking_url,
            ]);
        } elseif ($shipmentCount > 1) {
            $order->update([
                'tracking_number' => null,
                'tracking_url' => null,
            ]);
        }

        $carrierLabel = \App\Services\SellerFixedShippingService::carrierLabel($carrier);
        if ((string) $order->shipping_method !== $carrier && $order->market !== 'AE') {
            $order->update(['shipping_method' => $carrier]);
        }

        $label = \App\Services\SellerFulfillmentService::sellerLabel($user);
        $order->timeline()->create([
            'status' => 'shipped',
            'note' => $label . ' shipped via ' . $carrierLabel . ' — tracking ' . $trackingNumber,
        ]);

        \App\Services\SellerFulfillmentService::syncOrderStatus($order->fresh(['items', 'shipments']));

        WachatService::notifyOrderShipped($order->fresh('user'), $trackingNumber, $carrierLabel);

        return response()->json([
            'success' => true,
            'message' => 'Tracking saved. Delivery status will update automatically from ' . $carrierLabel . '.',
            'shipment' => [
                'id' => $shipment->id,
                'carrier' => $shipment->carrier,
                'tracking_number' => $shipment->tracking_number,
                'tracking_url' => $shipment->tracking_url,
                'status' => $shipment->status,
            ],
        ]);
    }

    /**
     * Update shipment status (seller or admin).
     */
    public function updateStatus(Request $request, int $shipmentId): JsonResponse
    {
        $request->validate(['status' => 'required|in:pending,shipped,in_transit,delivered']);

        $user = $request->user();
        $shipment = Shipment::with('order')->findOrFail($shipmentId);

        $order = $shipment->order;

        if ($order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot update status: order is cancelled.'], 422);
        }

        if ($order->hasOpenReturnOrDispute()) {
            return response()->json(['success' => false, 'message' => 'Cannot update status: order has an open return/refund request or dispute.'], 422);
        }

        if ($user->role === 'seller' && $user->seller?->store) {
            $allowed = $shipment->order->items()->where('store_id', $user->seller->store->id)->exists();
            if (!$allowed) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
        } elseif ($user->role === 'customer' && (int) $shipment->seller_id === (int) $user->id) {
            // Customer-as-seller: allowed to update their own shipment
        } elseif (!in_array($user->role, ['admin', 'sub_admin'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $shipment->update(['status' => $request->status]);
        if ($request->status === 'delivered') {
            $shipment->update(['delivered_at' => now()]);
            // Multi-seller: credit this seller as soon as THEIR portion is delivered
            \App\Services\PayoutService::creditSellerForDeliveredShipment($shipment->fresh(['order.items', 'store.seller']));
        }

        // Sync item fulfillment for this shipment's seller
        $order->load(['shipments', 'items']);
        $relatedItems = $order->items->filter(function ($item) use ($shipment) {
            if ($shipment->store_id) {
                return (int) $item->store_id === (int) $shipment->store_id;
            }
            return (int) $item->seller_id === (int) $shipment->seller_id && empty($item->store_id);
        });
        $itemStatus = match ($request->status) {
            'delivered' => 'delivered',
            'shipped', 'in_transit' => 'shipped',
            default => null,
        };
        if ($itemStatus && $relatedItems->isNotEmpty()) {
            \App\Models\OrderItem::whereIn('id', $relatedItems->pluck('id'))
                ->whereNotIn('fulfillment_status', ['rejected', 'cancelled'])
                ->update(['fulfillment_status' => $itemStatus]);
        }

        \App\Services\SellerFulfillmentService::syncOrderStatus($order->fresh(['items', 'shipments', 'refunds']));
        $order->refresh();
        $effectiveStatus = $order->status;
        if (in_array($effectiveStatus, ['delivered', 'completed'], true) && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
            $order->timeline()->create(['status' => $effectiveStatus, 'note' => 'Shipment status updated']);
            \App\Services\PayoutService::creditSellersForDeliveredOrderIfReleased($order->fresh());
            WachatService::notifyOrderDeliveredSellers($order->fresh());
        } else {
            $order->timeline()->create(['status' => $request->status, 'note' => 'Shipment status updated to ' . $request->status]);
        }

        return response()->json(['success' => true, 'message' => 'Status updated.', 'shipment' => $shipment->fresh()]);
    }
}
