<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\EDepartment;
use App\Models\ESubject;
use App\Models\EGroup;
use App\Observers\DepartmentObserver;
use App\Observers\SubjectObserver;
use App\Observers\GroupObserver;
use App\Services\Translation\MultiTenantTranslationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register MultiTenantTranslationService as singleton
        // This service provides HYBRID translation loading:
        // - Base translations from files (opcache - ultra fast)
        // - University-specific overrides from DB (cached)
        //
        // Performance:
        // - First request: ~17ms (load base + overrides)
        // - Subsequent: ~0.002ms (from cache)
        //
        // Usage in controllers:
        //   $service = app(MultiTenantTranslationService::class);
        //   $translations = $service->loadTranslations('menu');
        $this->app->singleton(MultiTenantTranslationService::class, function ($app) {
            return new MultiTenantTranslationService(
                locale: $app->getLocale(),
                universityId: config('app.university_id')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for automatic cache invalidation
        EDepartment::observe(DepartmentObserver::class);
        ESubject::observe(SubjectObserver::class);
        EGroup::observe(GroupObserver::class);

        // Configure rate limiters
        $this->configureRateLimiting();

        // Production safeguards
        if ($this->app->environment('production')) {
            $this->blockDangerousQueries();
        }

        // Query logging (dev only)
        if ($this->app->environment('local')) {
            DB::listen(function ($query) {
                logger()->debug('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            });
        }
    }

    /**
     * Configure the application's rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiter - 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Auth endpoints - env-driven rate limiting
        RateLimiter::for('auth', function (Request $request) {
            $maxAttempts = (int) env('THROTTLE_AUTH_MAX_ATTEMPTS', 5);
            $decayMinutes = (int) env('THROTTLE_AUTH_DECAY_MINUTES', 1);

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Juda ko\'p urinish amalga oshirildi. Iltimos ' . ($headers['Retry-After'] ?? 60) . ' soniyadan keyin qaytadan urinib ko\'ring.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Password reset endpoints - more restrictive
        RateLimiter::for('password', function (Request $request) {
            $maxAttempts = (int) env('THROTTLE_PASSWORD_MAX_ATTEMPTS', 3);
            $decayMinutes = (int) env('THROTTLE_PASSWORD_DECAY_MINUTES', 5);

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Juda ko\'p parol qayta tiklash urinishi. Iltimos ' . ($headers['Retry-After'] ?? 300) . ' soniyadan keyin qaytadan urinib ko\'ring.',
                        'retry_after' => $headers['Retry-After'] ?? 300,
                    ], 429);
                });
        });

        // Student CRUD - moderate limit (30 per minute for admins)
        RateLimiter::for('students', function (Request $request) {
            $limit = $request->user() ? 30 : 10; // Authenticated users get higher limit
            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429);
                });
        });

        // Public endpoints - more lenient (100 per minute)
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });
    }

    /**
     * Block dangerous SQL queries in production
     */
    private function blockDangerousQueries(): void
    {
        DB::listen(function ($query) {
            $sql = strtoupper($query->sql);

            $dangerous = [
                'DROP TABLE',
                'DROP DATABASE',
                'TRUNCATE TABLE',
                'ALTER TABLE',
                'DROP COLUMN',
                'DROP INDEX',
            ];

            foreach ($dangerous as $keyword) {
                if (strpos($sql, $keyword) !== false) {
                    throw new \Exception(
                        "❌ DANGEROUS QUERY BLOCKED IN PRODUCTION!\n" .
                        "Keyword: {$keyword}\n" .
                        "Use migrations for schema changes!"
                    );
                }
            }
        });
    }
}
