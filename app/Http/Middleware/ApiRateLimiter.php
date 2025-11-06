<?php

namespace App\Http\Middleware;

use App\Enums\RateLimitType;
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
        $limitType = $this->resolveRateLimitType($limit);
        $limiterKey = $this->resolveRequestKey($request, $limitType->value);
        $maxAttempts = $limitType->maxAttempts();

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
     * Resolve rate limit type from string
     */
    protected function resolveRateLimitType(string $limit): RateLimitType
    {
        return RateLimitType::tryFrom($limit) ?? RateLimitType::DEFAULT;
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
}
