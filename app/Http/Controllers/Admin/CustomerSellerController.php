<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserSegmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin People → Seller: customers who buy and also sell (C2C / private listings). */
class CustomerSellerController extends Controller
{
    public function index(Request $request): View
    {
        $query = UserSegmentService::customerSellersQuery()
            ->withCount(['products as private_listings_count' => fn ($q) => $q->where('seller_type', 'private')])
            ->orderByDesc('created_at');

        UserSegmentService::applySearch($query, $request->input('search'));
        UserSegmentService::applyStatus($query, $request->input('status'));

        if ($request->filled('kyc')) {
            $kyc = $request->input('kyc');
            if ($kyc === 'approved') {
                $query->where('is_private_seller', true);
            } elseif ($kyc === 'pending') {
                $query->where('private_seller_kyc_status', 'pending');
            } elseif ($kyc === 'casual') {
                $query->where('is_private_seller', false)->whereNull('private_seller_kyc_status');
            }
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.customer-sellers.index', compact('users'));
    }
}
