<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /** Parse date range from request (period in days or date_from/date_to). */
    private function getDateRange(Request $request): array
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay();
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay();
            return [$from, $to];
        }
        $period = (int) $request->get('period', 30);
        $period = min(365, max(1, $period));
        $to = now()->endOfDay();
        $from = now()->subDays($period)->startOfDay();
        return [$from, $to];
    }

    public function salesReport(Request $request): View
    {
        [$from, $to] = $this->getDateRange($request);
        $period = $request->get('period', '30');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $orderQuery = Order::where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        $summary = [
            'order_revenue' => (float) (clone $orderQuery)->sum('total'),
            'total_paid_orders' => (int) (clone $orderQuery)->count(),
            'avg_order_value' => (float) (clone $orderQuery)->avg('total'),
        ];

        $packageEarnings = (float) WalletTransaction::where('type', 'package_purchase')
            ->where('amount', '<', 0)
            ->whereBetween('created_at', [$from, $to])
            ->sum(DB::raw('ABS(amount)'));
        $summary['package_earnings'] = $packageEarnings;
        $summary['total_revenue'] = $summary['order_revenue'] + $packageEarnings;

        $ordersByDay = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ordersByMonth = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $orders = Order::with('user:id,name,email')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->paginate(10)->withQueryString();

        $perPage = 10;
        $ordersByDayPage = $request->get('orders_by_day_page', 1);
        $ordersByDayPaginator = new LengthAwarePaginator(
            $ordersByDay->forPage($ordersByDayPage, $perPage),
            $ordersByDay->count(),
            $perPage,
            $ordersByDayPage,
            ['path' => $request->url(), 'pageName' => 'orders_by_day_page'] + $request->query()
        );
        $ordersByMonthPage = $request->get('orders_by_month_page', 1);
        $ordersByMonthPaginator = new LengthAwarePaginator(
            $ordersByMonth->forPage($ordersByMonthPage, $perPage),
            $ordersByMonth->count(),
            $perPage,
            $ordersByMonthPage,
            ['path' => $request->url(), 'pageName' => 'orders_by_month_page'] + $request->query()
        );

        return view('admin.analytics.sales', compact('orders', 'summary', 'period', 'dateFrom', 'dateTo', 'ordersByDay', 'ordersByMonth', 'ordersByDayPaginator', 'ordersByMonthPaginator', 'from', 'to'));
    }

    public function sellerEarningReport(Request $request): View
    {
        [$from, $to] = $this->getDateRange($request);
        $period = $request->get('period', '30');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $items = OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        })
            ->selectRaw('seller_id, seller_type, store_id, COUNT(*) as order_count, SUM(quantity) as total_quantity, SUM(price * quantity) as gross_sales, SUM(COALESCE(discount_allocated, 0)) as coupon_discount, SUM(COALESCE(commission_amount, 0)) as total_commission, SUM(price * quantity) - SUM(COALESCE(discount_allocated, 0)) - SUM(COALESCE(commission_amount, 0)) as net_earnings')
            ->groupBy('seller_id', 'seller_type', 'store_id')
            ->orderByDesc('net_earnings')
            ->get();

        $sellerIds = $items->pluck('seller_id')->unique()->filter()->values()->all();
        $sellerNames = $sellerIds ? User::whereIn('id', $sellerIds)->get()->keyBy('id') : collect();

        $totals = [
            'gross_sales' => $items->sum('gross_sales'),
            'total_coupon_discount' => $items->sum('coupon_discount'),
            'total_commission' => $items->sum('total_commission'),
            'net_earnings' => $items->sum('net_earnings'),
            'order_count' => $items->sum('order_count'),
        ];

        $byDay = OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        })
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.price * order_items.quantity) as gross, SUM(COALESCE(order_items.discount_allocated, 0)) as coupon_discount, SUM(COALESCE(order_items.commission_amount, 0)) as commission, SUM(order_items.price * order_items.quantity) - SUM(COALESCE(order_items.discount_allocated, 0)) - SUM(COALESCE(order_items.commission_amount, 0)) as net')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('date')
            ->get();

        $perPage = 10;
        $itemsPage = $request->get('items_page', 1);
        $itemsPaginator = new LengthAwarePaginator($items->forPage($itemsPage, $perPage), $items->count(), $perPage, $itemsPage, ['path' => $request->url(), 'pageName' => 'items_page'] + $request->query());
        $byDayPage = $request->get('by_day_page', 1);
        $byDayPaginator = new LengthAwarePaginator($byDay->forPage($byDayPage, $perPage), $byDay->count(), $perPage, $byDayPage, ['path' => $request->url(), 'pageName' => 'by_day_page'] + $request->query());

        return view('admin.analytics.seller-earning', compact('items', 'totals', 'period', 'dateFrom', 'dateTo', 'from', 'to', 'byDay', 'byDayPaginator', 'itemsPaginator', 'sellerNames'));
    }

    public function commissionReport(Request $request): View
    {
        [$from, $to] = $this->getDateRange($request);
        $period = $request->get('period', '30');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $commissionTotal = (float) OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        })->sum('commission_amount');

        $byDay = OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        })
            ->selectRaw('DATE(orders.created_at) as date, SUM(COALESCE(order_items.commission_amount, 0)) as commission')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('date')
            ->get();

        $bySeller = OrderItem::whereHas('order', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$from, $to]);
        })
            ->selectRaw('seller_id, seller_type, SUM(COALESCE(commission_amount, 0)) as commission')
            ->groupBy('seller_id', 'seller_type')
            ->orderByDesc('commission')
            ->get();

        $rules = Commission::active()->ordered()->get();

        $perPage = 10;
        $byDayPage = $request->get('by_day_page', 1);
        $byDayPaginator = new LengthAwarePaginator($byDay->forPage($byDayPage, $perPage), $byDay->count(), $perPage, $byDayPage, ['path' => $request->url(), 'pageName' => 'by_day_page'] + $request->query());
        $bySellerPage = $request->get('by_seller_page', 1);
        $bySellerPaginator = new LengthAwarePaginator($bySeller->forPage($bySellerPage, $perPage), $bySeller->count(), $perPage, $bySellerPage, ['path' => $request->url(), 'pageName' => 'by_seller_page'] + $request->query());

        return view('admin.analytics.commission', compact('commissionTotal', 'byDay', 'bySeller', 'byDayPaginator', 'bySellerPaginator', 'rules', 'period', 'dateFrom', 'dateTo', 'from', 'to'));
    }

    public function payoutReport(Request $request): View
    {
        [$from, $to] = $this->getDateRange($request);
        $period = $request->get('period', '30');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Payout::with('user:id,name,email')->whereBetween('created_at', [$from, $to]);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $payouts = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $summary = [
            'total_amount' => (float) Payout::whereBetween('created_at', [$from, $to])->sum('amount'),
            'count' => (int) Payout::whereBetween('created_at', [$from, $to])->count(),
            'pending_amount' => (float) Payout::whereBetween('created_at', [$from, $to])->where('status', 'pending')->sum('amount'),
            'paid_amount' => (float) Payout::whereBetween('created_at', [$from, $to])->where('status', 'paid')->sum('amount'),
            'approved_amount' => (float) Payout::whereBetween('created_at', [$from, $to])->where('status', 'approved')->sum('amount'),
            'rejected_count' => (int) Payout::whereBetween('created_at', [$from, $to])->where('status', 'rejected')->count(),
        ];

        $byDay = Payout::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $perPage = 10;
        $byDayPage = $request->get('by_day_page', 1);
        $byDayPaginator = new LengthAwarePaginator($byDay->forPage($byDayPage, $perPage), $byDay->count(), $perPage, $byDayPage, ['path' => $request->url(), 'pageName' => 'by_day_page'] + $request->query());

        return view('admin.analytics.payout', compact('payouts', 'summary', 'period', 'dateFrom', 'dateTo', 'from', 'to', 'byDay', 'byDayPaginator'));
    }

    public function refundReport(Request $request): View
    {
        [$from, $to] = $this->getDateRange($request);
        $period = $request->get('period', '30');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Refund::with(['order:id,order_number', 'payment'])->whereBetween('created_at', [$from, $to]);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $refunds = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $summary = [
            'total_amount' => (float) Refund::whereBetween('created_at', [$from, $to])->sum('amount'),
            'count' => (int) Refund::whereBetween('created_at', [$from, $to])->count(),
            'pending_count' => (int) Refund::whereBetween('created_at', [$from, $to])->where('status', 'pending')->count(),
            'completed_count' => (int) Refund::whereBetween('created_at', [$from, $to])->where('status', 'completed')->count(),
        ];

        $byDay = Refund::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $perPage = 10;
        $byDayPage = $request->get('by_day_page', 1);
        $byDayPaginator = new LengthAwarePaginator($byDay->forPage($byDayPage, $perPage), $byDay->count(), $perPage, $byDayPage, ['path' => $request->url(), 'pageName' => 'by_day_page'] + $request->query());

        return view('admin.analytics.refund', compact('refunds', 'summary', 'period', 'dateFrom', 'dateTo', 'from', 'to', 'byDay', 'byDayPaginator'));
    }
}
