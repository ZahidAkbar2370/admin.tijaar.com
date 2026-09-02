<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Legacy commission settings URLs.
 * Customer fees & customer commission → Customer Settings.
 * Business seller commission → Seller Settings.
 * Advanced rules → Commissions index.
 */
class CommissionSettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.commissions.index');
    }

    public function customerOrder(): RedirectResponse
    {
        return redirect()->route('admin.people-settings.index', ['tab' => 'customer']);
    }

    public function updateCustomerOrder(): RedirectResponse
    {
        return redirect()->route('admin.people-settings.index', ['tab' => 'customer']);
    }

    public function privateSeller(): RedirectResponse
    {
        return redirect()->route('admin.customer-settings.index');
    }

    public function updatePrivateSeller(): RedirectResponse
    {
        return redirect()->route('admin.customer-settings.index');
    }

    public function seller(): RedirectResponse
    {
        return redirect()->route('admin.seller-settings.index');
    }

    public function updateSeller(): RedirectResponse
    {
        return redirect()->route('admin.seller-settings.index');
    }
}
