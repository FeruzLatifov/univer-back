<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Locale Middleware
 *
 * URL dan til parametrini oladi va Laravel ga o'rnatadi
 * Misol: /api/v1/student/profile?l=ru-RU
 *
 * Env-driven: APP_LOCALES and APP_LOCALE_DEFAULT
 */
class SetLocale
{
    /**
     * Til kodlarini mapping qilish (includes oz-UZ → oz)
     */
    protected array $localeMap = [
        'uz-UZ' => 'uz',
        'oz-UZ' => 'oz',
        'ru-RU' => 'ru',
        'en-US' => 'en',
        'uz' => 'uz',
        'oz' => 'oz',
        'ru' => 'ru',
        'en' => 'en',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. URL dan ?l= parametrini olish (Yii2 compat)
        $locale = $request->query('l');

        // 2. Agar URL da yo'q bo'lsa, header dan olish
        if (!$locale) {
            $locale = $request->header('Accept-Language');
            // Also check X-Locale header
            if (!$locale) {
                $locale = $request->header('X-Locale');
            }
        }

        // 3. Agar hali ham yo'q bo'lsa, user ning session dan olish
        if (!$locale && $request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        // 4. Default from env (fallback: uz)
        if (!$locale) {
            $locale = env('APP_LOCALE_DEFAULT', 'uz');
        }

        // 5. Til kodini normalizatsiya qilish (ru-RU -> ru, oz-UZ -> oz)
        $locale = $this->normalizeLocale($locale);

        // 6. Get allowed locales from env (fallback: uz,oz,ru,en)
        $allowedLocales = $this->getAllowedLocales();

        // 7. Faqat ruxsat berilgan tillarni qabul qilish
        if (!in_array($locale, $allowedLocales)) {
            $locale = env('APP_LOCALE_DEFAULT', 'uz');
        }

        // 8. Laravel ga tilni o'rnatish
        app()->setLocale($locale);

        // 9. Session ga saqlash (keyingi so'rovlar uchun)
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Get allowed locales from env
     */
    protected function getAllowedLocales(): array
    {
        $locales = env('APP_LOCALES', 'uz,oz,ru,en');
        return array_map('trim', explode(',', $locales));
    }

    /**
     * Til kodini normalizatsiya qilish
     */
    protected function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        $allowedLocales = $this->getAllowedLocales();

        if (isset($this->localeMap[$locale])) {
            return $this->localeMap[$locale];
        }

        if (str_contains($locale, '-')) {
            $parts = explode('-', $locale);
            $shortCode = $parts[0];
            if (in_array($shortCode, $allowedLocales)) {
                return $shortCode;
            }
        }

        if (str_contains($locale, ',')) {
            $parts = explode(',', $locale);
            foreach ($parts as $part) {
                $part = trim(explode(';', $part)[0]);
                $normalized = $this->normalizeLocale($part);
                if (in_array($normalized, $allowedLocales)) {
                    return $normalized;
                }
            }
        }

        return env('APP_LOCALE_DEFAULT', 'uz');
    }
}
