<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at->toIso8601String(),
                'is_current' => $t->id === $request->user()->currentAccessToken()?->id,
            ]);
        return response()->json(['success' => true, 'sessions' => $tokens]);
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        $tokenModel = $request->user()->tokens()->find($token);
        if (!$tokenModel) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        if ($tokenModel->id === $request->user()->currentAccessToken()?->id) {
            return response()->json(['success' => false, 'message' => 'Cannot revoke current session'], 400);
        }
        $tokenModel->delete();
        return response()->json(['success' => true, 'message' => 'Session revoked']);
    }
}
