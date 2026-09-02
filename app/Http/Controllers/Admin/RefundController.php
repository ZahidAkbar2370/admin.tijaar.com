<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $query = Refund::with(['order:id,order_number,user_id', 'payment', 'order.user:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refunds = $query->paginate(20)->withQueryString();
        return view('admin.refunds.index', compact('refunds'));
    }

    public function show(Refund $refund): View
    {
        $refund->load(['order.items', 'payment', 'order.user']);
        return view('admin.refunds.show', compact('refund'));
    }

    public function process(Request $request, Refund $refund)
    {
        $request->validate([
            'refund_type' => 'required|in:gateway,wallet',
            'gateway_refund_id' => 'nullable|string|max:255',
        ]);

        if ($refund->status !== 'pending') {
            return back()->with('error', 'Refund is not pending.');
        }

        try {
            RefundService::processRefund(
                $refund,
                $request->refund_type,
                $request->gateway_refund_id,
                auth()->id()
            );
            return back()->with('success', 'Refund processed successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
