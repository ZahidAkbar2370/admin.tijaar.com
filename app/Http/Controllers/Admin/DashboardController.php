<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $period = (int) request()->get('period', 30);
        $since = now()->subDays($period);

        $stats = [
            'users' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'sellers' => User::where('role', 'seller')->count(),
            'stores' => Store::count(),
            'products' => Product::where('status', 'published')->count(),
            'orders_total' => Order::count(),
            'orders_pending' => Order::whereIn('status', ['pending', 'processing'])->count(),
            'revenue_period' => Order::where('payment_status', 'paid')->where('created_at', '>=', $since)->sum('total'),
            'orders_period' => Order::where('created_at', '>=', $since)->count(),
            'low_stock_count' => Product::where('track_inventory', true)->whereNotNull('low_stock_threshold')->whereColumn('quantity', '<=', 'low_stock_threshold')->where('quantity', '>', 0)->count(),
            'out_of_stock_count' => Product::where(function ($q) {
                $q->where('track_inventory', true)->orWhereNull('track_inventory');
            })->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                    })->where('quantity', '<=', 0);
                })->orWhere(function ($q2) {
                    $q2->where('product_type', 'variable')
                        ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) <= 0');
                });
            })->count(),
        ];

        $recentOrders = Order::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'order_number', 'user_id', 'total', 'status', 'payment_status', 'created_at']);

        $recentCustomers = User::where('role', 'customer')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'name', 'email', 'created_at']);

        $chartRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartOrders = Order::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact('stats', 'period', 'recentOrders', 'recentCustomers', 'chartRevenue', 'chartOrders'));
    }
}
