<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationRead;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function counts(Request $request): JsonResponse
    {
        $userId = auth()->id();
        return response()->json([
            'customers' => AdminNotificationRead::unreadCount($userId, 'new_customers'),
            'sellers' => AdminNotificationRead::unreadCount($userId, 'new_sellers'),
            'orders_pending' => Order::whereIn('status', ['pending', 'processing'])->count(),
            'disputes_pending' => Dispute::whereNotIn('status', ['resolved', 'rejected', 'refunded'])->count(),
            'payouts_pending' => Payout::where('status', 'pending')->count(),
            'conversations' => Conversation::where('updated_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $request->validate(['type' => 'required|in:new_customers,new_sellers,orders_pending,disputes_pending,payouts_pending,conversations']);

        if (in_array($request->type, ['new_customers', 'new_sellers'])) {
            AdminNotificationRead::markRead(auth()->id(), $request->type);
        }

        return response()->json(['success' => true]);
    }
}
