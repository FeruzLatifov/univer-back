<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Tizim xabarlari modeli (Asl matnlar)
 *
 * @property int $id
 * @property string|null $category
 * @property string $message
 * @property SystemMessageTranslation[] $translations
 */
class SystemMessage extends Model
{
    protected $table = 'e_system_message';

    public $timestamps = false;

    protected $fillable = [
        'category',
        'message',
    ];

    /**
     * Translations relationship
     * Contains both base and custom translations
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SystemMessageTranslation::class, 'id', 'id');
    }

    /**
     * Get translation for specific language
     * Priority: custom_translation > translation > original message
     */
    public function getTranslation(string $language): ?string
    {
        $translation = $this->translations()
            ->where('language', $language)
            ->first();

        if (!$translation) {
            return $this->message;
        }

        // Priority: custom_translation > translation
        return $translation->custom_translation ?? $translation->translation ?? $this->message;
    }

    /**
     * Get all translations with custom overrides
     * Returns: [
     *   'uz-UZ' => [
     *     'base' => '...', 
     *     'custom' => '...', 
     *     'is_custom' => true,
     *     'final' => '...' (custom or base)
     *   ]
     * ]
     */
    public function getAllTranslations(): array
    {
        $result = [];

        foreach ($this->translations as $translation) {
            $hasCustom = !empty($translation->custom_translation);
            
            $result[$translation->language] = [
                'base' => $translation->translation,
                'custom' => $translation->custom_translation,
                'is_custom' => $hasCustom,
                'final' => $hasCustom ? $translation->custom_translation : $translation->translation,
            ];
        }

        return $result;
    }

    /**
     * Tarjimalarni saqlash/yangilash
     */
    public function saveTranslations(array $translations): bool
    {
        foreach ($translations as $language => $translation) {
            SystemMessageTranslation::updateOrCreate(
                [
                    'id' => $this->id,
                    'language' => $language,
                ],
                [
                    'translation' => $translation,
                ]
            );
        }

        return true;
    }

    /**
     * Yangi xabar yaratish yoki mavjudini topish
     */
    public static function findOrCreate(string $message, string $category = 'app'): self
    {
        return static::firstOrCreate(
            [
                'category' => $category,
                'message' => $message,
            ]
        );
    }

    /**
     * Clear all translation caches
     */
    public static function clearCache(): void
    {
        Cache::tags(['translations'])->flush();

        \Log::info('Translation cache cleared');
    }
}
