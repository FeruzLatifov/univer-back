<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HLanguage;
use App\Models\ESystemMessage;
use App\Models\ESystemMessageTranslation;
use App\Services\LanguageMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

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
     * ✅ Fixed: Uses ISO codes (uz, ru) instead of Yii2 INTEGER codes (11, 12)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $allowedLocales = $this->getAllowedLocales();

        $languages = HLanguage::active()
            ->ordered()
            ->get()
            ->map(function ($language) {
                return [
                    'code' => $language->iso_code,  // ✅ ISO code (uz, ru, ...)
                    'yii_code' => $language->code,  // Original INTEGER code (for debugging)
                    'name' => $language->name,
                    'native_name' => $language->native_name ?: LanguageMapper::getNativeName($language->iso_code),
                    'position' => $language->position,
                    'active' => $language->active,
                    '_translations' => $language->_translations,
                ];
            })
            ->filter(function ($language) use ($allowedLocales) {
                // Filter by ISO code and check if mapping exists
                return $language['code'] !== null && in_array($language['code'], $allowedLocales);
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
     * ✅ Fixed: Finds by ISO code, converts to Yii2 INTEGER code
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $currentLocale = App::getLocale(); // ISO code (uz, ru, ...)
        
        // Convert ISO to Yii2 INTEGER code
        $yiiCode = LanguageMapper::toYii($currentLocale);
        
        if (!$yiiCode) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }
        
        $language = HLanguage::find($yiiCode);

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $language->iso_code,  // ISO code
                'name' => $language->name,
                'native_name' => $language->native_name ?: LanguageMapper::getNativeName($language->iso_code),
                'position' => $language->position,
            ],
        ]);
    }

    /**
     * Set language (env-driven validation)
     * POST /api/v1/languages/set with { "locale": "uz|oz|ru|en" }
     * 
     * ✅ Fixed: Accepts ISO code, finds by Yii2 INTEGER code
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

        $locale = $request->input('locale'); // ISO code (uz, ru, ...)
        
        // Convert ISO to Yii2 INTEGER code
        $yiiCode = LanguageMapper::toYii($locale);
        
        if (!$yiiCode) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }

        // Check if language exists and is active
        $language = HLanguage::where('code', $yiiCode)->where('active', true)->first();

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }

        // Set application locale (ISO code)
        App::setLocale($locale);

        // Store in session for subsequent requests
        Session::put('locale', $locale);

        $this->persistUserPreference($locale);

        return response()->json([
            'success' => true,
            'message' => __('language.change_success'),
            'data' => [
                'locale' => $locale,  // ISO code
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
     * Persist user language preference if authenticated
     */
    protected function persistUserPreference(string $locale): void
    {
        $fullLocale = $this->mapShortToFull($locale);

        $admin = auth('employee-api')->user() ?? auth('admin-api')->user();
        if ($admin && $admin->language !== $fullLocale) {
            $admin->language = $fullLocale;
            $admin->save();
        }

    }

    /**
     * Get language by code
     * 
     * ✅ Fixed: Accepts ISO code, finds by Yii2 INTEGER code
     *
     * @param string $code ISO code (uz, ru, ...)
     * @return JsonResponse
     */
    public function show(string $code): JsonResponse
    {
        // Convert ISO to Yii2 INTEGER code
        $yiiCode = LanguageMapper::toYii($code);
        
        if (!$yiiCode) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }
        
        $language = HLanguage::find($yiiCode);

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => __('language.not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $language->iso_code,  // ISO code
                'yii_code' => $language->code,  // Original INTEGER code
                'name' => $language->name,
                'native_name' => $language->native_name ?: LanguageMapper::getNativeName($language->iso_code),
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
     * ✅ Fixed: Uses Yii2 database tables (e_system_message + e_system_message_translation)
     *
     * @return JsonResponse
     */
    public function getTranslations(): JsonResponse
    {
        $cacheKey = 'frontend_translations_all';

        // Cache for 1 hour
        $translations = Cache::remember($cacheKey, 3600, function () {
            $allowedLocales = $this->getAllowedLocales();

            // Get all active languages with ISO codes
            $languages = HLanguage::active()
                ->ordered()
                ->get()
                ->filter(function ($language) use ($allowedLocales) {
                    return $language->iso_code !== null && in_array($language->iso_code, $allowedLocales);
                })
                ->mapWithKeys(function ($language) {
                    return [$language->iso_code => $language->code]; // ['uz' => 11, 'ru' => 12, ...]
                })
                ->toArray();

            // Get translations for each language
            $result = [];
            foreach ($languages as $isoCode => $yiiCode) {
                // Get all messages with their translations for this language
                // Join e_system_message with e_system_message_translation
                $messages = ESystemMessage::query()
                    ->select('e_system_message.category', 'e_system_message.message', 'e_system_message_translation.translation')
                    ->leftJoin('e_system_message_translation', function ($join) use ($yiiCode) {
                        $join->on('e_system_message.id', '=', 'e_system_message_translation.id')
                             ->where('e_system_message_translation.language', '=', $yiiCode);
                    })
                    ->get();

                // Group by category
                $categorized = $messages->groupBy('category')->map(function ($items) {
                    return $items->pluck('translation', 'message')->filter()->toArray();
                })->toArray();

                $result[$isoCode] = ['translation' => $categorized];
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }

    /**
     * Map short locale code (uz) to full locale code (uz-UZ)
     */
    protected function mapShortToFull(string $locale): string
    {
        $map = [
            'uz' => 'uz-UZ',
            'oz' => 'oz-UZ',
            'ru' => 'ru-RU',
            'en' => 'en-US',
        ];

        return $map[$locale] ?? $locale;
    }
}
