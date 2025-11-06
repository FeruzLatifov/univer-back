<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiting Middleware
 * 
 * Implements role-based rate limiting for API endpoints
 * - Public endpoints: 30 requests per minute
 * - Student endpoints: 80 requests per minute
 * - Teacher endpoints: 100 requests per minute
 * - Admin endpoints: 120 requests per minute
 * 
 * @package App\Http\Middleware
 */
class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limit = 'default'): Response
    {
        $limiterKey = $this->resolveRequestKey($request, $limit);
        $maxAttempts = $this->getMaxAttempts($limit);

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($limiterKey);
            
            return response()->json([
                'success' => false,
                'message' => 'Juda ko\'p so\'rovlar yuborildi. Iltimos kutib qaytadan urinib ko\'ring.',
                'error' => 'Too many requests',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        RateLimiter::hit($limiterKey, 60); // 60 seconds decay

        $response = $next($request);

        // Add rate limit headers to response
        $remaining = RateLimiter::remaining($limiterKey, $maxAttempts);
        
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }

    /**
     * Resolve the rate limiter key for the request
     */
    protected function resolveRequestKey(Request $request, string $limit): string
    {
        $user = $request->user();
        
        if ($user) {
            return "api:{$limit}:{$user->id}";
        }

        // For unauthenticated requests, use IP address
        return "api:{$limit}:{$request->ip()}";
    }

    /**
     * Get maximum attempts based on limit type
     */
    protected function getMaxAttempts(string $limit): int
    {
        return match($limit) {
            'public' => 30,      // Public endpoints
            'student' => 80,     // Student endpoints
            'teacher' => 100,    // Teacher endpoints
            'admin' => 120,      // Admin endpoints
            'auth' => 10,        // Authentication endpoints (strict)
            default => 60,       // Default rate limit
        };
    }
}
