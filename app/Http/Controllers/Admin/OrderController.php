<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierShipmentPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'shippingAddress', 'shipments', 'items'])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product.media', 'items.store', 'user', 'shippingAddress', 'timeline', 'payments', 'coupon', 'shipments.store']);

        foreach ($order->shipments as $shipment) {
            $productNames = $order->items->filter(function ($item) use ($shipment) {
                if ($shipment->store_id) {
                    return (int) $item->store_id === (int) $shipment->store_id;
                }

                return (int) $item->seller_id === (int) $shipment->seller_id;
            })->pluck('product_name')->unique()->values()->all();
            CourierShipmentPresenter::enrich($shipment);
            $shipment->setAttribute('product_names', $productNames);
        }

        // Per-item seller product ordered status (from that seller's shipment)
        foreach ($order->items as $item) {
            $shipment = $order->shipments->first(function ($s) use ($item) {
                if ($s->store_id) {
                    return (int) $item->store_id === (int) $s->store_id;
                }
                return (int) $item->seller_id === (int) $s->seller_id;
            });
            $item->seller_portion_status = $shipment ? $shipment->status : (in_array($order->status, ['cancelled', 'refunded']) ? $order->status : 'pending');
        }

        // Variant images: load variants for items that have variant_id so we show the ordered variant image
        $variantIds = $order->items->map(function ($i) {
            return $i->options['variant_id'] ?? null;
        })->filter()->unique()->values()->all();
        $variants = !empty($variantIds) ? \App\Models\ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

        foreach ($order->items as $item) {
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            $variant = $variantId > 0 ? $variants->get($variantId) : null;
            $variantImagePath = $variant ? ($variant->image_path ?? (is_array($variant->image_paths ?? null) && !empty($variant->image_paths) ? $variant->image_paths[0] : null)) : null;
            $item->item_image_url = $item->resolveImageUrl($variantImagePath);
        }

        // Only show items with price > 0 (hide ghost/duplicate zero-value lines)
        $orderItems = $order->items->filter(function ($i) {
            return (float) $i->price > 0;
        });

        $customerFees = \App\Services\OrderFeeBreakdown::customerFees($order);
        $sellerFees = \App\Services\OrderFeeBreakdown::sellerFeesFromItems($orderItems->values(), $order);

        return view('admin.orders.show', compact('order', 'orderItems', 'customerFees', 'sellerFees'));
    }

    /**
     * Manually approve an online payment (JazzCash/Easypaisa/etc.).
     * Use when gateway inquiry fails / sandbox mismatch but money was confirmed offline.
     */
    public function markPaymentPaid(Order $order)
    {
        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            return back()->with('error', 'Cannot mark payment on a cancelled or refunded order.');
        }

        $onlineMethods = ['jazzcash', 'jazzcash_partial', 'easypaisa', 'stripe', 'paypal'];
        if (! in_array((string) $order->payment_method, $onlineMethods, true)) {
            return back()->with('error', 'Manual payment approval is only for online payment methods.');
        }

        if (in_array((string) $order->payment_status, ['paid', 'partial_paid'], true)) {
            return back()->with('warning', 'Payment is already marked as ' . $order->payment_status . '.');
        }

        $payment = $order->payments()->latest('id')->first();
        if ($payment && $payment->status !== 'completed') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => array_merge(
                    is_array($payment->gateway_response) ? $payment->gateway_response : [],
                    [
                        'manual_approved' => true,
                        'manual_approved_at' => now()->toIso8601String(),
                        'manual_approved_by' => auth()->id(),
                    ]
                ),
            ]);
        }

        \App\Services\OrderWorkflowService::markPaymentSuccess($order->fresh(), 'Payment manually approved by admin');

        $order->refresh();
        $newPaymentStatus = $order->payment_status;

        // Courier auto-book only after seller approval; admin can still retry manually.
        return back()->with(
            'success',
            'Payment marked as ' . $newPaymentStatus . '. Order is processing — seller must approve before shipping.'
        );
    }
}
