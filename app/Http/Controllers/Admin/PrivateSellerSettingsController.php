<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\MarketplaceFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Private seller program: switches, media limits, fees, and verification gates.
 */
class PrivateSellerSettingsController extends Controller
{
    public function index(): View
    {
        return redirect()->route('admin.people-settings.index', ['tab' => 'private_seller']);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'private_sellers_enabled' => 'required|in:0,1',
            'private_listing_approval' => 'required|in:0,1',
            'private_listing_max_images' => 'required|integer|min:1|max:12',
            'private_listing_max_image_updates' => 'required|integer|min:0|max:100',
            'private_listing_video_enabled' => 'required|in:0,1',
            'private_seller_commission_type' => 'required|in:fixed,percentage',
            'private_seller_commission_value' => 'required|numeric|min:0|max:999999',
            'private_seller_marketplace_fee_type' => 'required|in:fixed,percentage',
            'private_seller_marketplace_fee_value' => 'required|numeric|min:0|max:999999',
            'private_seller_online_transaction_fee_type' => 'required|in:fixed,percentage',
            'private_seller_online_transaction_fee_value' => 'required|numeric|min:0|max:999999',
            'private_seller_must_verify_email' => 'required|in:0,1',
            'private_seller_must_verify_phone' => 'required|in:0,1',
            'private_seller_must_verify_whatsapp' => 'required|in:0,1',
            'order_reject_penalty_private_seller' => 'required|numeric|min:0|max:1000000',
        ]);

        Setting::set('private_sellers_enabled', $request->private_sellers_enabled);
        Setting::set('private_listing_approval', $request->private_listing_approval);
        Setting::set('private_listing_max_images', (string) max(1, min(12, (int) $request->private_listing_max_images)));
        Setting::set('private_listing_max_image_updates', (string) max(0, min(100, (int) $request->private_listing_max_image_updates)));
        Setting::set('private_listing_video_enabled', (string) (int) $request->private_listing_video_enabled);
        Setting::set('private_seller_commission_type', $request->private_seller_commission_type);
        Setting::set('private_seller_commission_value', (string) $request->private_seller_commission_value);
        Setting::set('private_seller_marketplace_fee_type', $request->private_seller_marketplace_fee_type);
        Setting::set('private_seller_marketplace_fee_value', (string) $request->private_seller_marketplace_fee_value);
        Setting::set('private_seller_online_transaction_fee_type', $request->private_seller_online_transaction_fee_type);
        Setting::set('private_seller_online_transaction_fee_value', (string) $request->private_seller_online_transaction_fee_value);
        Setting::set('private_seller_must_verify_email', $request->private_seller_must_verify_email);
        Setting::set('private_seller_must_verify_phone', $request->private_seller_must_verify_phone);
        Setting::set('private_seller_must_verify_whatsapp', $request->private_seller_must_verify_whatsapp);
        Setting::set('order_reject_penalty_private_seller', (string) round((float) $request->order_reject_penalty_private_seller, 2));

        MarketplaceFeeService::syncPrivateSellerCommissionRule();

        return redirect()->route('admin.people-settings.index', ['tab' => 'private_seller'])->with('success', 'Private seller settings updated.');
    }
}
