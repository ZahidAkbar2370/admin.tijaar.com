<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierShipmentPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackOrderController extends Controller
{
    /** Active orders with courier shipments and latest provider response. */
    public function index(Request $request): View
    {
        $activeStatuses = ['pending', 'paid', 'processing', 'approved', 'shipped'];

        $query = Order::query()
            ->with(['user', 'shipments.store'])
            ->whereIn('status', $activeStatuses)
            ->whereHas('shipments')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('order_number', 'like', "%{$q}%")
                    ->orWhere('tracking_number', 'like', "%{$q}%")
                    ->orWhereHas('shipments', fn ($s) => $s
                        ->where('tracking_number', 'like', "%{$q}%")
                        ->orWhere('lcs_cn_number', 'like', "%{$q}%")
                        ->orWhere('tcs_cn_number', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('carrier')) {
            $query->whereHas('shipments', fn ($s) => $s->where('carrier', $request->carrier));
        }

        $orders = $query->paginate(20)->withQueryString();

        foreach ($orders as $order) {
            foreach ($order->shipments as $shipment) {
                CourierShipmentPresenter::enrich($shipment);
            }
        }

        return view('admin.track-orders.index', compact('orders'));
    }
}
