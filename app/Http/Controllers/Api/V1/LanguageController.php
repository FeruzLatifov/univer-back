<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HLanguage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Language Controller
 *
 * Handles language switching and language list retrieval
 */
class LanguageController extends Controller
{
    /**
     * Get all active languages
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $languages = HLanguage::active()
            ->ordered()
            ->get()
            ->map(function ($language) {
                return [
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'position' => $language->position,
                    'active' => $language->active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $languages,
            'current' => App::getLocale(),
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
     * Set language
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:uz,ru,en',
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
}
