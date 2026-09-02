<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['order.user'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('gateway_reference', 'like', "%{$q}%")
                    ->orWhere('gateway', 'like', "%{$q}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    public function show(Payment $transaction): View
    {
        $transaction->load(['order.user', 'order.items', 'logs', 'refunds']);

        return view('admin.transactions.show', ['payment' => $transaction]);
    }
}
