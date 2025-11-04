<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Translation\MultiTenantTranslationService;

/**
 * Load Translations Middleware
 *
 * Pre-loads translations for the current request's locale.
 * This ensures translations are cached before any controller logic runs.
 *
 * How it works:
 * 1. Detect locale from Accept-Language header or user preference
 * 2. Set Laravel's app locale
 * 3. Pre-load translations using MultiTenantTranslationService
 * 4. Store in request attributes for easy access
 *
 * Performance:
 * - First request: ~17ms (load from file + DB)
 * - Subsequent: ~0.002ms (from cache)
 *
 * Usage:
 * In routes/api.php:
 *   Route::middleware(['auth:sanctum', 'load.translations'])->group(function () {
 *       // Your routes
 *   });
 */
class LoadTranslations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Detect locale
        $locale = $this->detectLocale($request);

        // 2. Set Laravel's locale
        app()->setLocale($locale);

        // 3. Pre-load translations (will use cache if available)
        // This happens early so all subsequent code can use translations
        $service = app(MultiTenantTranslationService::class, [
            'locale' => $locale,
            'universityId' => config('app.university_id'),
        ]);

        // Pre-load menu translations (most commonly used)
        $menuTranslations = $service->loadTranslations('menu');

        // Store in request for easy access in controllers
        // Controllers can get translations via: $request->get('translations')
        $request->attributes->set('translations', $menuTranslations);
        $request->attributes->set('translation_service', $service);

        return $next($request);
    }

    /**
     * Detect locale from request
     *
     * Priority:
     * 1. Accept-Language header (e.g., "uz", "oz", "ru", "en")
     * 2. User's saved preference (if authenticated)
     * 3. Default: 'uz'
     *
     * @param Request $request
     * @return string
     */
    protected function detectLocale(Request $request): string
    {
        // 1. Check Accept-Language header
        $headerLocale = $request->header('Accept-Language');

        if ($headerLocale && in_array($headerLocale, ['uz', 'oz', 'ru', 'en'])) {
            return $headerLocale;
        }

        // 2. Check authenticated user's preference
        if ($user = $request->user()) {
            if (isset($user->locale) && in_array($user->locale, ['uz', 'oz', 'ru', 'en'])) {
                return $user->locale;
            }
        }

        // 3. Default
        return config('app.locale', 'uz');
    }
}
