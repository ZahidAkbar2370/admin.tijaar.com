<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function suspend(Request $request, User $user): JsonResponse
    {
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Cannot suspend admin.'], 403);
        }
        $user->update(['is_suspended' => true]);
        return response()->json([
            'success' => true,
            'message' => 'User suspended.',
            'user' => $user->fresh()->makeHidden(['password']),
        ]);
    }

    public function unsuspend(Request $request, User $user): JsonResponse
    {
        $user->update(['is_suspended' => false]);
        return response()->json([
            'success' => true,
            'message' => 'User unsuspended.',
            'user' => $user->fresh()->makeHidden(['password']),
        ]);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Cannot ban admin.'], 403);
        }
        $user->update(['is_banned' => true]);
        $user->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'User banned.',
            'user' => $user->fresh()->makeHidden(['password']),
        ]);
    }

    public function unban(Request $request, User $user): JsonResponse
    {
        $user->update(['is_banned' => false]);
        return response()->json([
            'success' => true,
            'message' => 'User unbanned.',
            'user' => $user->fresh()->makeHidden(['password']),
        ]);
    }
}
