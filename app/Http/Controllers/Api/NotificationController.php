<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Paginated list of push notification log for the current user.
     * GET /notifications
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 1 && $perPage <= 100 ? $perPage : 15;
        $unreadOnly = filter_var($request->get('unread_only'), FILTER_VALIDATE_BOOLEAN);

        $query = $request->user()->pushNotifications()->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(fn (PushNotification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data ?? (object) [],
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mark notification(s) as read.
     * POST /notifications/mark-read
     */
    public function markRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => ['nullable', 'array'],
            'notification_ids.*' => ['integer'],
            'all' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if ($request->boolean('all')) {
            $user->pushNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        } else {
            $ids = $request->input('notification_ids', []);
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provide notification_ids or all=true',
                ], 422);
            }
            $user->pushNotifications()->whereIn('id', $ids)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
        ]);
    }

    /**
     * Unread count for push notifications.
     * GET /notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->pushNotifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
