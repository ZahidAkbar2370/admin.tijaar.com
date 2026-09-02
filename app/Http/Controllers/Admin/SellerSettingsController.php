<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MarketplaceFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer-as-seller settings: listings, seller-side order deductions, and business seller rules.
 */
class SellerSettingsController extends Controller
{
    public function index(): View
    {
        return redirect()->route('admin.people-settings.index', ['tab' => 'seller']);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'private_listing_free_limit' => 'required|integer|min:0|max:100',
            'private_listing_limit' => 'required|integer|min:1|max:100',
            'private_listing_fee' => 'required|numeric|min:0|max:100000',
            'private_listing_expiry_days' => 'required|integer|min:1|max:365',
            'private_listing_max_images' => 'required|integer|min:1|max:12',
            'private_listing_video_enabled' => 'required|in:0,1',
            'private_seller_commission_type' => 'required|in:fixed,percentage',
            'private_seller_commission_value' => 'required|numeric|min:0|max:999999',
            'private_seller_marketplace_fee_type' => 'required|in:fixed,percentage',
            'private_seller_marketplace_fee_value' => 'required|numeric|min:0|max:999999',
            'private_seller_online_transaction_fee_type' => 'required|in:fixed,percentage',
            'private_seller_online_transaction_fee_value' => 'required|numeric|min:0|max:999999',
            'product_approval_required' => 'required|in:0,1',
            'payout_hold_days' => 'required|integer|min:0|max:90',
            'seller_commission_type' => 'required|in:fixed,percentage',
            'seller_commission_value' => 'required|numeric|min:0|max:999999',
            'order_reject_penalty_customer_seller' => 'required|numeric|min:0|max:1000000',
        ]);

        $free = (int) $request->private_listing_free_limit;
        $max = (int) $request->private_listing_limit;
        if ($max < $free) {
            return back()
                ->withErrors(['private_listing_limit' => 'Max product limit must be greater than or equal to the free listing limit.'])
                ->withInput();
        }

        Setting::set('private_listing_free_limit', (string) $free);
        Setting::set('private_listing_limit', (string) $max);
        Setting::set('private_listing_fee', (string) round((float) $request->private_listing_fee, 2));
        Setting::set('private_listing_expiry_days', (string) (int) $request->private_listing_expiry_days);
        Setting::set('private_listing_max_images', (string) max(1, min(12, (int) $request->private_listing_max_images)));
        Setting::set('private_listing_video_enabled', (string) (int) $request->private_listing_video_enabled);

        Setting::set('private_seller_commission_type', $request->private_seller_commission_type);
        Setting::set('private_seller_commission_value', (string) $request->private_seller_commission_value);
        Setting::set('private_seller_marketplace_fee_type', $request->private_seller_marketplace_fee_type);
        Setting::set('private_seller_marketplace_fee_value', (string) $request->private_seller_marketplace_fee_value);
        Setting::set('private_seller_online_transaction_fee_type', $request->private_seller_online_transaction_fee_type);
        Setting::set('private_seller_online_transaction_fee_value', (string) $request->private_seller_online_transaction_fee_value);

        Setting::set('product_approval_required', $request->product_approval_required);
        Setting::set('payout_hold_days', (string) max(0, min(90, (int) $request->payout_hold_days)));
        Setting::set('seller_commission_type', $request->seller_commission_type);
        Setting::set('seller_commission_value', (string) $request->seller_commission_value);
        Setting::set('order_reject_penalty_customer_seller', (string) round((float) $request->order_reject_penalty_customer_seller, 2));

        MarketplaceFeeService::syncPrivateSellerCommissionRule();
        MarketplaceFeeService::syncGlobalSellerCommissionRule();

        return redirect()->route('admin.people-settings.index', ['tab' => 'seller'])->with('success', 'Seller settings updated.');
    }
}
