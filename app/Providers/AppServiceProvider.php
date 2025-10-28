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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TranslationServiceProvider for database-driven translations
        $this->app->register(TranslationServiceProvider::class);
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

        // Auth endpoints - more restrictive (5 login attempts per minute)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
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
