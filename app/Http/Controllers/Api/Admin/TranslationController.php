<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\SystemMessage;
use App\Models\System\SystemMessageTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Translation Management Controller
 *
 * Admin uchun tarjimalarni boshqarish API
 * univer-yii2 dagi system/translation ga o'xshash
 */
class TranslationController extends Controller
{
    /**
     * Tarjimalar ro'yxati (pagination + search + filter)
     *
     * GET /api/admin/translations?language=ru-RU&search=welcome&page=1
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->get('language', 'uz-UZ');
        $search = $request->get('search', '');
        $category = $request->get('category', '');
        $perPage = $request->get('per_page', 20);

        $query = SystemMessage::with(['translations' => function ($q) use ($language) {
            $q->where('language', $language);
        }]);

        // Qidirish (message yoki translation bo'yicha)
        if ($search) {
            $query->where(function ($q) use ($search, $language) {
                $q->where('message', 'ILIKE', "%{$search}%")
                    ->orWhereHas('translations', function ($q) use ($search, $language) {
                        $q->where('language', $language)
                            ->where('translation', 'ILIKE', "%{$search}%");
                    });
            });
        }

        // Category bo'yicha filtr
        if ($category) {
            $query->where('category', $category);
        }

        // Oxirgi qo'shilganlar birinchi
        $query->orderBy('id', 'desc');

        $messages = $query->paginate($perPage);

        // Response formatini yaxshilash
        $data = $messages->map(function ($message) use ($language) {
            $translation = $message->translations->first();

            return [
                'id' => $message->id,
                'category' => $message->category,
                'message' => $message->message,
                'translation' => $translation?->translation ?? '',
                'all_translations' => $message->getAllTranslations(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
        ]);
    }

    /**
     * Bitta tarjimani olish
     *
     * GET /api/admin/translations/{id}
     */
    public function show(int $id): JsonResponse
    {
        $message = SystemMessage::with('translations')->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Tarjima topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'category' => $message->category,
                'message' => $message->message,
                'translations' => $message->getAllTranslations(),
            ],
        ]);
    }

    /**
     * Yangi tarjima qo'shish
     *
     * POST /api/admin/translations
     * Body: {
     *   "category": "app",
     *   "message": "New Message",
     *   "translations": {
     *     "uz-UZ": "Yangi xabar",
     *     "ru-RU": "Новое сообщение",
     *     "en-US": "New Message"
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:32',
            'message' => 'required|string',
            'translations' => 'required|array',
            'translations.uz-UZ' => 'required|string',
            'translations.ru-RU' => 'nullable|string',
            'translations.en-US' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mavjud xabarni tekshirish
        $exists = SystemMessage::where('category', $request->category)
            ->where('message', $request->message)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Bu xabar allaqachon mavjud',
            ], 409);
        }

        // Yangi xabar yaratish
        $message = SystemMessage::create([
            'category' => $request->category,
            'message' => $request->message,
        ]);

        // Tarjimalarni saqlash
        $message->saveTranslations($request->translations);

        // Cache tozalash
        $this->clearTranslationCache();

        return response()->json([
            'success' => true,
            'message' => 'Tarjima muvaffaqiyatli qo\'shildi',
            'data' => [
                'id' => $message->id,
                'category' => $message->category,
                'message' => $message->message,
                'translations' => $request->translations,
            ],
        ], 201);
    }

    /**
     * Tarjimani yangilash
     *
     * PUT /api/admin/translations/{id}
     * Body: {
     *   "translations": {
     *     "uz-UZ": "Yangilangan matn",
     *     "ru-RU": "Обновленный текст",
     *     "en-US": "Updated text"
     *   }
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $message = SystemMessage::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Tarjima topilmadi',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:32',
            'message' => 'sometimes|string',
            'translations' => 'sometimes|array',
            'translations.uz-UZ' => 'nullable|string',
            'translations.ru-RU' => 'nullable|string',
            'translations.en-US' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Category va message ni yangilash (agar kerak bo'lsa)
        if ($request->has('category')) {
            $message->category = $request->category;
        }
        if ($request->has('message')) {
            $message->message = $request->message;
        }
        $message->save();

        // Tarjimalarni yangilash
        if ($request->has('translations')) {
            $message->saveTranslations($request->translations);
        }

        // Cache tozalash
        $this->clearTranslationCache();

        return response()->json([
            'success' => true,
            'message' => 'Tarjima muvaffaqiyatli yangilandi',
            'data' => [
                'id' => $message->id,
                'category' => $message->category,
                'message' => $message->message,
                'translations' => $message->getAllTranslations(),
            ],
        ]);
    }

    /**
     * Tarjimani o'chirish
     *
     * DELETE /api/admin/translations/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $message = SystemMessage::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Tarjima topilmadi',
            ], 404);
        }

        $messageText = $message->message;
        $message->delete();

        // Cache tozalash
        $this->clearTranslationCache();

        return response()->json([
            'success' => true,
            'message' => "Tarjima [{$messageText}] o'chirildi",
        ]);
    }

    /**
     * Barcha kategoriyalarni olish
     *
     * GET /api/admin/translations/categories
     */
    public function categories(): JsonResponse
    {
        $categories = SystemMessage::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Statistika
     *
     * GET /api/admin/translations/stats
     */
    public function stats(): JsonResponse
    {
        $totalMessages = SystemMessage::count();
        $totalTranslations = \DB::table('e_system_message_translation')->count();

        $byLanguage = \DB::table('e_system_message_translation')
            ->select('language', \DB::raw('COUNT(*) as count'))
            ->groupBy('language')
            ->get()
            ->pluck('count', 'language');

        $byCategory = SystemMessage::select('category', \DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category');

        return response()->json([
            'success' => true,
            'data' => [
                'total_messages' => $totalMessages,
                'total_translations' => $totalTranslations,
                'by_language' => $byLanguage,
                'by_category' => $byCategory,
            ],
        ]);
    }

    /**
     * Cache tozalash
     *
     * POST /api/admin/translations/clear-cache
     */
    public function clearCache(): JsonResponse
    {
        $this->clearTranslationCache();

        return response()->json([
            'success' => true,
            'message' => 'Translation cache tozalandi',
        ]);
    }

    /**
     * Import translations from CSV
     *
     * POST /api/admin/translations/import
     * Body: multipart/form-data with "file" field
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // max 10MB
            'force' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Upload file to temp location
            $file = $request->file('file');
            $path = $file->storeAs('temp', 'import_' . time() . '.csv');
            $fullPath = storage_path('app/' . $path);

            // Run import command
            $force = $request->boolean('force');
            
            Artisan::call('translation:import', [
                '--file' => $fullPath,
                '--force' => $force,
            ]);

            $output = Artisan::output();

            // Delete temp file
            Storage::delete($path);

            // Parse output for statistics
            preg_match('/Total rows processed\s+│\s+([\d,]+)/', $output, $total);
            preg_match('/Messages created\s+│\s+([\d,]+)/', $output, $created);
            preg_match('/Translations updated\s+│\s+([\d,]+)/', $output, $updated);

            return response()->json([
                'success' => true,
                'message' => 'Tarjimalar muvaffaqiyatli import qilindi',
                'data' => [
                    'total_rows' => isset($total[1]) ? (int)str_replace(',', '', $total[1]) : 0,
                    'messages_created' => isset($created[1]) ? (int)str_replace(',', '', $created[1]) : 0,
                    'translations_updated' => isset($updated[1]) ? (int)str_replace(',', '', $updated[1]) : 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import xatosi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export translations to CSV
     *
     * GET /api/admin/translations/export?include_custom=1&only_custom=0&languages=uz-UZ,ru-RU
     */
    public function export(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $validator = Validator::make($request->all(), [
            'include_custom' => 'nullable|boolean',
            'only_custom' => 'nullable|boolean',
            'languages' => 'nullable|string', // comma-separated
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $filename = 'translations_export_' . time() . '.csv';
            $path = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Build command arguments
            $args = [
                '--file' => $path,
            ];

            if ($request->boolean('include_custom')) {
                $args['--include-custom'] = true;
            }

            if ($request->boolean('only_custom')) {
                $args['--only-custom'] = true;
            }

            if ($request->has('languages')) {
                $args['--languages'] = $request->input('languages');
            }

            // Run export command
            Artisan::call('translation:export', $args);

            // Return file as download
            return response()->download($path, $filename, [
                'Content-Type' => 'text/csv',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export xatosi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transliterate uz-UZ to oz-UZ
     *
     * POST /api/admin/translations/transliterate
     * Body: { "force": true, "from": "uz-UZ", "to": "oz-UZ" }
     */
    public function transliterate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|string|in:uz-UZ,oz-UZ,ru-RU,en-US,kk-UZ,tg-TG,kz-KZ,tm-TM,kg-KG',
            'to' => 'nullable|string|in:uz-UZ,oz-UZ,ru-RU,en-US,kk-UZ,tg-TG,kz-KZ,tm-TM,kg-KG',
            'force' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $args = [];

            if ($request->has('from')) {
                $args['--from'] = $request->input('from');
            }

            if ($request->has('to')) {
                $args['--to'] = $request->input('to');
            }

            if ($request->boolean('force')) {
                $args['--force'] = true;
            }

            // Run transliterate command
            Artisan::call('translation:transliterate', $args);

            $output = Artisan::output();

            // Parse output for statistics
            preg_match('/Total processed\s+│\s+([\d,]+)/', $output, $total);
            preg_match('/Created\s+│\s+([\d,]+)/', $output, $created);
            preg_match('/Updated\s+│\s+([\d,]+)/', $output, $updated);

            return response()->json([
                'success' => true,
                'message' => 'Transliteratsiya muvaffaqiyatli yakunlandi',
                'data' => [
                    'total_processed' => isset($total[1]) ? (int)str_replace(',', '', $total[1]) : 0,
                    'created' => isset($created[1]) ? (int)str_replace(',', '', $created[1]) : 0,
                    'updated' => isset($updated[1]) ? (int)str_replace(',', '', $updated[1]) : 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transliteratsiya xatosi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update custom translation (university-specific)
     *
     * POST /api/admin/translations/{id}/custom
     * Body: { "language": "uz-UZ", "custom_translation": "Asosiy sahifa" }
     */
    public function updateCustom(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|in:uz-UZ,oz-UZ,ru-RU,en-US,kk-UZ,tg-TG,kz-KZ,tm-TM,kg-KG',
            'custom_translation' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = SystemMessage::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Xabar topilmadi',
            ], 404);
        }

        try {
            $language = $request->input('language');
            $customTranslation = $request->input('custom_translation');

            // Update or create translation
            SystemMessageTranslation::updateOrCreate(
                [
                    'id' => $id,
                    'language' => $language,
                ],
                [
                    'custom_translation' => $customTranslation,
                ]
            );

            // Clear cache
            $this->clearTranslationCache();

            return response()->json([
                'success' => true,
                'message' => 'Maxsus tarjima saqlandi',
                'data' => [
                    'id' => $id,
                    'language' => $language,
                    'custom_translation' => $customTranslation,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xato: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete custom translation (revert to base)
     *
     * DELETE /api/admin/translations/{id}/custom/{language}
     */
    public function deleteCustom(int $id, string $language): JsonResponse
    {
        $message = SystemMessage::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Xabar topilmadi',
            ], 404);
        }

        try {
            $translation = SystemMessageTranslation::where('id', $id)
                ->where('language', $language)
                ->first();

            if (!$translation || !$translation->custom_translation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maxsus tarjima topilmadi',
                ], 404);
            }

            // Clear custom translation (revert to base)
            $translation->custom_translation = null;
            $translation->save();

            // Clear cache
            $this->clearTranslationCache();

            return response()->json([
                'success' => true,
                'message' => 'Maxsus tarjima o\'chirildi (asl tarjimaga qaytarildi)',
                'data' => [
                    'id' => $id,
                    'language' => $language,
                    'base_translation' => $translation->translation,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xato: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Cache ni tozalash (ichki metod)
     */
    private function clearTranslationCache(): void
    {
        $locales = ['uz-UZ', 'ru-RU', 'en-US'];
        $groups = ['app', 'yii'];

        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                Cache::forget("translations.{$locale}.{$group}");
            }
        }

        // Umumiy translation cache ni ham tozalash
        Cache::tags(['translations'])->flush();
    }
}
