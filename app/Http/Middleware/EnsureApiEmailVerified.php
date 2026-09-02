<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\AuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks authenticated API actions when admin has enabled email verification
 * and the user has not verified their email yet.
 */
class EnsureApiEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if (!AuthController::isEmailVerificationRequired()) {
            return $next($request);
        }

        if ($user->email_verified_at) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please verify your email before continuing.',
            'requires_verification' => true,
            'email' => $user->email,
            'error_code' => 'email_unverified',
        ], 403);
    }
}
