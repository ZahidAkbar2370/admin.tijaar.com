<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MarketplaceFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer-as-buyer settings: checkout fees and payout hold.
 */
class CustomerSettingsController extends Controller
{
    public function index(): View
    {
        return redirect()->route('admin.people-settings.index', ['tab' => 'customer']);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'private_payout_hold_days' => 'nullable|integer|min:0|max:90',
            'buyer_marketplace_fee_type' => 'required|in:fixed,percentage',
            'buyer_marketplace_fee_value' => 'required|numeric|min:0|max:999999',
            'buyer_online_transaction_fee_type' => 'required|in:fixed,percentage',
            'buyer_online_transaction_fee_value' => 'required|numeric|min:0|max:999999',
        ]);

        Setting::set(
            'private_payout_hold_days',
            $request->filled('private_payout_hold_days') ? (string) (int) $request->private_payout_hold_days : ''
        );

        Setting::set('buyer_marketplace_fee_type', $request->buyer_marketplace_fee_type);
        Setting::set('buyer_marketplace_fee_value', (string) $request->buyer_marketplace_fee_value);
        Setting::set('buyer_online_transaction_fee_type', $request->buyer_online_transaction_fee_type);
        Setting::set('buyer_online_transaction_fee_value', (string) $request->buyer_online_transaction_fee_value);

        return redirect()->route('admin.people-settings.index', ['tab' => 'customer'])->with('success', 'Customer settings updated.');
    }
}
