<?php

namespace App\Services;

use App\Models\System\SystemMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;

/**
 * Database dan tarjimalarni yuklash servisi
 *
 * Yii2 DbMessageSource ga o'xshash ishlaydi:
 * - Tarjimalar database da saqlanadi
 * - Cache ishlatiladi (3600 sekund)
 * - Missing translations avtomatik yaratiladi
 */
class DatabaseTranslationLoader extends FileLoader
{
    /**
     * Database dan tarjimalarni yuklash
     *
     * @param string $locale Til kodi (uz, ru, en)
     * @param string $group Category (app)
     * @param string|null $namespace Namespace (null = default)
     * @return array
     */
    public function load($locale, $group, $namespace = null): array
    {
        // Agar namespace ko'rsatilgan bo'lsa, parent dan yuklash (file-based)
        if ($namespace && $namespace !== '*') {
            return parent::load($locale, $group, $namespace);
        }

        // Til kodini to'liq formatga o'zgartirish
        $fullLocale = $this->getFullLocale($locale);

        // Cache kaliti
        $cacheKey = "translations.{$fullLocale}.{$group}";

        // Cache dan olish (3600 sekund = 1 soat)
        return Cache::remember($cacheKey, 3600, function () use ($fullLocale, $group) {
            return $this->loadFromDatabase($fullLocale, $group);
        });
    }

    /**
     * Database dan tarjimalarni yuklash (ichki metod)
     */
    protected function loadFromDatabase(string $locale, string $category): array
    {
        $translations = [];

        // Barcha xabarlarni olish
        $messages = SystemMessage::with(['translations' => function ($query) use ($locale) {
            $query->where('language', $locale);
        }])
            ->where('category', $category)
            ->get();

        foreach ($messages as $message) {
            $translation = $message->translations->first();
            $translations[$message->message] = $translation
                ? $translation->translation
                : $message->message; // Agar tarjima yo'q bo'lsa, asl matnni qaytarish
        }

        return $translations;
    }

    /**
     * Til kodini to'liq formatga o'zgartirish
     * uz -> uz-UZ
     * ru -> ru-RU
     * en -> en-US
     */
    protected function getFullLocale(string $locale): string
    {
        $map = [
            'uz' => 'uz-UZ',
            'ru' => 'ru-RU',
            'en' => 'en-US',
        ];

        return $map[$locale] ?? $locale;
    }

    /**
     * Missing translation ni yaratish
     *
     * Agar tarjima topilmasa, avtomatik database ga qo'shadi
     */
    public function addMissingTranslation(string $key, string $locale, string $group = 'app'): void
    {
        // Xabarni database ga qo'shish
        $message = SystemMessage::findOrCreate($key, $group);

        // Agar tarjima yo'q bo'lsa, bo'sh tarjima qo'shish
        $fullLocale = $this->getFullLocale($locale);
        $exists = $message->translations()->where('language', $fullLocale)->exists();

        if (!$exists) {
            $message->saveTranslations([
                $fullLocale => $key, // Asl matnni tarjima sifatida qo'shish
            ]);
        }

        // Cache ni tozalash
        $cacheKey = "translations.{$fullLocale}.{$group}";
        Cache::forget($cacheKey);
    }

    /**
     * Cache ni tozalash
     */
    public function clearCache(): void
    {
        $locales = ['uz-UZ', 'ru-RU', 'en-US'];
        $groups = ['app', 'yii'];

        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                Cache::forget("translations.{$locale}.{$group}");
            }
        }
    }
}
