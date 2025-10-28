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
 */
class SetLocale
{
    /**
     * Ruxsat berilgan tillar
     */
    protected array $allowedLocales = ['uz', 'ru', 'en'];

    /**
     * Til kodlarini mapping qilish
     */
    protected array $localeMap = [
        'uz-UZ' => 'uz',
        'ru-RU' => 'ru',
        'en-US' => 'en',
        'uz' => 'uz',
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
        }

        // 3. Agar hali ham yo'q bo'lsa, user ning session dan olish
        if (!$locale && $request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        // 4. Default: uz
        if (!$locale) {
            $locale = config('app.locale', 'uz');
        }

        // 5. Til kodini normalizatsiya qilish (ru-RU -> ru)
        $locale = $this->normalizeLocale($locale);

        // 6. Faqat ruxsat berilgan tillarni qabul qilish
        if (!in_array($locale, $this->allowedLocales)) {
            $locale = 'uz';
        }

        // 7. Laravel ga tilni o'rnatish
        app()->setLocale($locale);

        // 8. Session ga saqlash (keyingi so'rovlar uchun)
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Til kodini normalizatsiya qilish
     */
    protected function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        if (isset($this->localeMap[$locale])) {
            return $this->localeMap[$locale];
        }

        if (str_contains($locale, '-')) {
            $parts = explode('-', $locale);
            $shortCode = $parts[0];
            if (in_array($shortCode, $this->allowedLocales)) {
                return $shortCode;
            }
        }

        if (str_contains($locale, ',')) {
            $parts = explode(',', $locale);
            foreach ($parts as $part) {
                $part = trim(explode(';', $part)[0]);
                $normalized = $this->normalizeLocale($part);
                if (in_array($normalized, $this->allowedLocales)) {
                    return $normalized;
                }
            }
        }

        return 'uz';
    }
}
