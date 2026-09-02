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
 * Unified People → Settings with tabs: customer | seller | private_seller
 */
class PeopleSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'customer');
        if (!in_array($tab, ['customer', 'seller', 'private_seller'], true)) {
            $tab = 'customer';
        }

        $customerSettings = [
            'private_payout_hold_days' => Setting::get('private_payout_hold_days', ''),
            'buyer_marketplace_fee_type' => MarketplaceFeeService::buyerMarketplaceFeeType(),
            'buyer_marketplace_fee_value' => (string) MarketplaceFeeService::buyerMarketplaceFeeValue(),
            'buyer_online_transaction_fee_type' => MarketplaceFeeService::buyerOnlineTransactionFeeType(),
            'buyer_online_transaction_fee_value' => (string) MarketplaceFeeService::buyerOnlineTransactionFeeValue(),
        ];

        $sellerSettings = [
            'private_listing_free_limit' => Setting::get('private_listing_free_limit', '3'),
            'private_listing_limit' => Setting::get('private_listing_limit', '15'),
            'private_listing_fee' => Setting::get('private_listing_fee', '50'),
            'private_listing_expiry_days' => Setting::get('private_listing_expiry_days', '30'),
            'private_listing_max_images' => Setting::get('private_listing_max_images', '6'),
            'private_listing_video_enabled' => Setting::get('private_listing_video_enabled', '0'),
            'private_seller_commission_type' => MarketplaceFeeService::privateSellerCommissionType(),
            'private_seller_commission_value' => (string) MarketplaceFeeService::privateSellerCommissionValue(),
            'private_seller_marketplace_fee_type' => MarketplaceFeeService::privateSellerMarketplaceFeeType(),
            'private_seller_marketplace_fee_value' => (string) MarketplaceFeeService::privateSellerMarketplaceFeeValue(),
            'private_seller_online_transaction_fee_type' => MarketplaceFeeService::privateSellerOnlineTransactionFeeType(),
            'private_seller_online_transaction_fee_value' => (string) MarketplaceFeeService::privateSellerOnlineTransactionFeeValue(),
            'product_approval_required' => Setting::get('product_approval_required', '0'),
            'payout_hold_days' => Setting::get('payout_hold_days', '0'),
            'seller_commission_type' => MarketplaceFeeService::sellerCommissionType(),
            'seller_commission_value' => (string) MarketplaceFeeService::sellerCommissionValue(),
            'order_reject_penalty_customer_seller' => Setting::get('order_reject_penalty_customer_seller', '500'),
        ];

        $privateSellerSettings = [
            'private_sellers_enabled' => Setting::get('private_sellers_enabled', '1'),
            'private_listing_approval' => Setting::get('private_listing_approval', '0'),
            'private_listing_max_images' => Setting::get('private_listing_max_images', '6'),
            'private_listing_max_image_updates' => Setting::get('private_listing_max_image_updates', '0'),
            'private_listing_video_enabled' => Setting::get('private_listing_video_enabled', '0'),
            'private_seller_commission_type' => MarketplaceFeeService::privateSellerCommissionType(),
            'private_seller_commission_value' => (string) MarketplaceFeeService::privateSellerCommissionValue(),
            'private_seller_marketplace_fee_type' => MarketplaceFeeService::privateSellerMarketplaceFeeType(),
            'private_seller_marketplace_fee_value' => (string) MarketplaceFeeService::privateSellerMarketplaceFeeValue(),
            'private_seller_online_transaction_fee_type' => MarketplaceFeeService::privateSellerOnlineTransactionFeeType(),
            'private_seller_online_transaction_fee_value' => (string) MarketplaceFeeService::privateSellerOnlineTransactionFeeValue(),
            'private_seller_must_verify_email' => Setting::get('private_seller_must_verify_email', '0'),
            'private_seller_must_verify_phone' => Setting::get('private_seller_must_verify_phone', '0'),
            'private_seller_must_verify_whatsapp' => Setting::get('private_seller_must_verify_whatsapp', '0'),
            'order_reject_penalty_private_seller' => Setting::get('order_reject_penalty_private_seller', '1000'),
        ];

        return view('admin.people-settings.index', [
            'tab' => $tab,
            'customerSettings' => $customerSettings,
            'sellerSettings' => $sellerSettings,
            'privateSellerSettings' => $privateSellerSettings,
            'globalPayoutHoldDays' => (int) (Setting::get('payout_hold_days', '0') ?: 0),
            'codEnabled' => (string) Setting::get('payment_cod_enabled', '1') === '1',
            'pendingKyc' => User::where('private_seller_kyc_status', 'pending')->count(),
        ]);
    }

    public function updateCustomer(Request $request): RedirectResponse
    {
        return app(CustomerSettingsController::class)->update($request);
    }

    public function updateSeller(Request $request): RedirectResponse
    {
        return app(SellerSettingsController::class)->update($request);
    }

    public function updatePrivateSeller(Request $request): RedirectResponse
    {
        return app(PrivateSellerSettingsController::class)->update($request);
    }
}
