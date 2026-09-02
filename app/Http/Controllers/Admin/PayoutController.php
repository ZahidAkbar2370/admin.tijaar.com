<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Setting;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payout::with('user')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('payout_number', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        $payouts = $query->paginate(20)->withQueryString();
        $config = [
            'payout_min_threshold' => Setting::get('payout_min_threshold') ?: 1000,
            'private_payout_threshold' => Setting::get('private_payout_threshold') ?: 500,
        ];

        return view('admin.payouts.index', compact('payouts', 'config'));
    }

    public function show(Payout $payout): View
    {
        $payout->load(['user', 'items.orderItem.order']);
        return view('admin.payouts.show', compact('payout'));
    }

    public function approve(Payout $payout): \Illuminate\Http\RedirectResponse
    {
        if ($payout->status !== 'pending') {
            return redirect()->back()->with('error', 'Payout is not pending.');
        }
        try {
            PayoutService::debitSellerWalletForPayout($payout, 'Payout #' . $payout->payout_number . ' approved');
            PayoutService::approvePayout($payout, auth()->id());
            $payout->user?->notify(new \App\Notifications\PayoutApprovedNotification($payout));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: 'Failed to approve payout.');
        }
        return redirect()->back()->with('success', 'Payout approved.');
    }

    public function reject(Request $request, Payout $payout): \Illuminate\Http\RedirectResponse
    {
        if ($payout->status !== 'pending') {
            return redirect()->back()->with('error', 'Payout is not pending.');
        }
        $request->validate(['reason' => 'required|string|max:500']);
        PayoutService::rejectPayout($payout, $request->reason, auth()->id());
        PayoutService::creditWalletBackForRejectedPayout($payout);
        return redirect()->back()->with('success', 'Payout rejected.');
    }

    public function markPaid(Payout $payout): \Illuminate\Http\RedirectResponse
    {
        if (!in_array($payout->status, ['pending', 'approved'])) {
            return redirect()->back()->with('error', 'Invalid status.');
        }
        PayoutService::markPaid($payout);
        return redirect()->back()->with('success', 'Payout marked as paid.');
    }

    public function updateConfig(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'payout_min_threshold' => 'nullable|numeric|min:0',
            'private_payout_threshold' => 'nullable|numeric|min:0',
        ]);
        if ($request->filled('payout_min_threshold')) {
            Setting::set('payout_min_threshold', $request->payout_min_threshold);
        }
        if ($request->filled('private_payout_threshold')) {
            Setting::set('private_payout_threshold', $request->private_payout_threshold);
        }
        return redirect()->route('admin.payouts.index')->with('success', 'Payout config updated.');
    }
}
