<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // API V1 Routes
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api_v1.php'));

            // Legacy API Routes (redirect to v1)
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'student.status' => \App\Http\Middleware\CheckStudentStatus::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sentry Integration - Report all exceptions to Sentry
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry') && config('sentry.dsn')) {
                app('sentry')->captureException($e);
            }
        });

        // Handle model not found exceptions
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ma\'lumot topilmadi',
                    'error' => 'Resource not found',
                ], 404);
            }
        });

        // Handle authentication exceptions
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autentifikatsiya talab qilinadi',
                    'error' => 'Unauthenticated',
                ], 401);
            }
        });

        // Handle authorization exceptions
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sizda bu amalni bajarish uchun ruxsat yo\'q',
                    'error' => 'Forbidden',
                ], 403);
            }
        });

        // Handle validation exceptions (already handled by FormRequest, but as fallback)
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle JWT exceptions
        $exceptions->renderable(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token muddati tugagan',
                    'error' => 'Token expired',
                ], 401);
            }
        });

        $exceptions->renderable(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token noto\'g\'ri',
                    'error' => 'Token invalid',
                ], 401);
            }
        });

        $exceptions->renderable(function (\Tymon\JWTAuth\Exceptions\JWTException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token yo\'q',
                    'error' => 'Token absent',
                ], 401);
            }
        });

        // Handle database exceptions
        $exceptions->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->is('api/*')) {
                // Don't expose SQL errors in production
                if (app()->environment('production')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Database xatosi yuz berdi',
                        'error' => 'Database error',
                    ], 500);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Database xatosi: ' . $e->getMessage(),
                    'error' => 'Database error',
                ], 500);
            }
        });

        // Handle throttle exceptions (rate limiting)
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Juda ko\'p so\'rovlar yuborildi. Iltimos biroz kutib qaytadan urinib ko\'ring.',
                    'error' => 'Too many requests',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429);
            }
        });

        // Generic exception handler for all other exceptions
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                // Log the error
                logger()->error('API Error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't expose error details in production
                if (app()->environment('production')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kutilmagan xatolik yuz berdi',
                        'error' => 'Internal server error',
                    ], 500);
                }

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
        });
    })->create();
