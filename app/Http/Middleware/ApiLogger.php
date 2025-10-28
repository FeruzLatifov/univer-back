<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Logger Middleware
 *
 * Best Practice: Log all API requests for monitoring and debugging
 * Logs: Request method, URL, IP, user, response status, duration
 */
class ApiLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Process request
        $response = $next($request);

        // Calculate duration
        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms

        // Get authenticated user info
        $userId = null;
        $userType = null;

        if ($request->user('staff-api')) {
            $userId = $request->user('staff-api')->id;
            $userType = 'staff';
        } elseif ($request->user('student-api')) {
            $userId = $request->user('student-api')->id;
            $userType = 'student';
        }

        // Log the request
        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
            'user_type' => $userType,
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
        ]);

        // Log errors separately
        if ($response->getStatusCode() >= 400) {
            Log::channel('api')->warning('API Error Response', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'user_id' => $userId,
                'user_type' => $userType,
                'response' => $response->getContent(),
            ]);
        }

        return $response;
    }
}
