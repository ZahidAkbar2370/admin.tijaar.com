<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin', 'sub_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        return $next($request);
    }
}
