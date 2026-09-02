<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records successful mutating requests (POST/PUT/PATCH/DELETE) into activities.
 */
class LogActivityMiddleware
{
    /** Paths that should not create activity noise. */
    protected array $skipPathContains = [
        'notifications/fcm-token',
        'notifications/mark-read',
        'notifications/unread-count',
        'shipping/estimate',
        'shipping/calculate',
        'payment/preview',
        'coupons/validate',
        'smtp-test',
        'settings/test-email',
        'whatsapp/send-otp',
        'whatsapp/verify-otp',
        'activities',
        'webhooks/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($request->attributes->get('activity_logged')) {
                return $response;
            }

            if (! $this->shouldLog($request, $response)) {
                return $response;
            }

            ActivityLogger::logFromRequest($request);
        } catch (\Throwable $e) {
            // never break the response
        }

        return $response;
    }

    protected function shouldLog(Request $request, Response $response): bool
    {
        $method = strtoupper($request->method());
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $path = strtolower($request->path());
        foreach ($this->skipPathContains as $needle) {
            if (str_contains($path, $needle)) {
                return false;
            }
        }

        $status = $response->getStatusCode();
        $isApi = str_starts_with($path, 'api/');
        $isAdmin = str_starts_with($path, 'admin/');

        if ($isApi) {
            return $status >= 200 && $status < 300;
        }

        if ($isAdmin) {
            // Direct JSON/HTML success
            if ($status >= 200 && $status < 300) {
                return true;
            }
            // Most admin forms redirect after save — require success flash to avoid logging validation failures
            if (in_array($status, [301, 302, 303, 307, 308], true) && $request->hasSession()) {
                return $request->session()->has('success');
            }
        }

        return false;
    }
}
