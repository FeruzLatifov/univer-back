<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HLanguage;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

/**
 * Language Controller
 *
 * Handles language switching and language list retrieval
 * Env-driven: APP_LOCALES and APP_LOCALE_DEFAULT
 */
class LanguageController extends Controller
{
    /**
     * Get all active languages (filtered by env-driven allowed locales)
     * Returns { success, data: { languages, current } }
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $allowedLocales = $this->getAllowedLocales();

        $languages = HLanguage::active()
            ->ordered()
            ->get()
            ->filter(function ($language) use ($allowedLocales) {
                return in_array($language->code, $allowedLocales);
            })
            ->map(function ($language) {
                return [
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'position' => $language->position,
                    'active' => $language->active,
                    '_translations' => $language->_translations,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'languages' => $languages,
                'current' => App::getLocale(),
            ],
        ]);
    }

    /**
     * Get current language
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $currentLocale = App::getLocale();
        $language = HLanguage::find($currentLocale);

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => 'Current language not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'position' => $language->position,
            ],
        ]);
    }

    /**
     * Set language (env-driven validation)
     * POST /api/v1/languages/set with { "locale": "uz|oz|ru|en" }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setLanguage(Request $request): JsonResponse
    {
        $allowedLocales = $this->getAllowedLocales();

        $request->validate([
            'locale' => [
                'required',
                'string',
                'in:' . implode(',', $allowedLocales),
            ],
        ]);

        $locale = $request->input('locale');

        // Check if language exists and is active
        $language = HLanguage::where('code', $locale)->where('active', true)->first();

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => 'Language not found or inactive',
            ], 404);
        }

        // Set application locale
        App::setLocale($locale);

        // Store in session for subsequent requests
        Session::put('locale', $locale);

        return response()->json([
            'success' => true,
            'message' => 'Language changed successfully',
            'data' => [
                'locale' => $locale,
                'name' => $language->name,
            ],
        ]);
    }

    /**
     * Get allowed locales from env
     *
     * @return array
     */
    protected function getAllowedLocales(): array
    {
        $locales = env('APP_LOCALES', 'uz,oz,ru,en');
        return array_map('trim', explode(',', $locales));
    }

    /**
     * Get language by code
     *
     * @param string $code
     * @return JsonResponse
     */
    public function show(string $code): JsonResponse
    {
        $language = HLanguage::find($code);

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => 'Language not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'position' => $language->position,
                'active' => $language->active,
                '_translations' => $language->_translations,
            ],
        ]);
    }

    /**
     * Get frontend translations for all active languages
     * Returns translations grouped by language code
     * GET /api/v1/languages/translations
     *
     * @return JsonResponse
     */
    public function getTranslations(): JsonResponse
    {
        $cacheKey = 'frontend_translations';

        // Cache for 1 hour
        $translations = Cache::remember($cacheKey, 3600, function () {
            $allowedLocales = $this->getAllowedLocales();

            // Get all active languages
            $languages = HLanguage::active()
                ->ordered()
                ->get()
                ->filter(function ($language) use ($allowedLocales) {
                    return in_array($language->code, $allowedLocales);
                })
                ->pluck('code')
                ->toArray();

            // Get translations for each language
            $result = [];
            foreach ($languages as $locale) {
                // Get all translations for this locale from Translation model
                $localeTranslations = Translation::where('language', $locale)
                    ->get()
                    ->groupBy('category')
                    ->map(function ($translations) {
                        return $translations->pluck('translation', 'message')->toArray();
                    })
                    ->toArray();

                $result[$locale] = ['translation' => $localeTranslations];
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }
}
