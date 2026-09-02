<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dispute::with(['order', 'user:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('dispute_number', 'like', "%{$q}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $disputes = $query->paginate(20)->withQueryString();

        return view('admin.disputes.index', compact('disputes'));
    }

    public function show(Dispute $dispute): View
    {
        $dispute->load(['order.items', 'user', 'messages.user', 'evidence']);
        return view('admin.disputes.show', compact('dispute'));
    }

    public function arbitrate(Request $request, Dispute $dispute)
    {
        $request->validate([
            'action' => 'required|in:resolve,reject',
            'notes' => 'nullable|string|max:2000',
        ]);

        $dispute->update([
            'status' => $request->action === 'resolve' ? 'resolved' : 'rejected',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $request->notes,
        ]);

        DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => auth()->id(),
            'body' => "Admin: " . ($request->action === 'resolve' ? 'Resolved' : 'Rejected') . ($request->notes ? " - {$request->notes}" : ''),
            'is_admin' => true,
        ]);

        return back()->with('success', 'Dispute ' . $request->action . 'd');
    }

    public function addMessage(Request $request, Dispute $dispute)
    {
        $request->validate(['body' => 'required|string|max:5000']);

        DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
            'is_admin' => true,
        ]);

        return back()->with('success', 'Message added');
    }

    public function processRefund(Dispute $dispute)
    {
        if ($dispute->status !== 'resolved') {
            return back()->with('error', 'Dispute must be resolved first');
        }

        DB::transaction(function () use ($dispute) {
            $dispute->order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            $dispute->update(['status' => 'refunded']);
        });

        return back()->with('success', 'Refund processed');
    }
}
