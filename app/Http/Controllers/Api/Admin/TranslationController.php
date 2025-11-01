<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\SystemMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
