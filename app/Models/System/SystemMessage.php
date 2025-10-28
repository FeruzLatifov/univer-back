<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Tarjimalar bilan bog'lanish
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SystemMessageTranslation::class, 'id', 'id');
    }

    /**
     * Ma'lum bir til uchun tarjima olish
     */
    public function getTranslation(string $language): ?string
    {
        $translation = $this->translations()
            ->where('language', $language)
            ->first();

        return $translation?->translation ?? $this->message;
    }

    /**
     * Barcha tillar uchun tarjimalarni olish
     */
    public function getAllTranslations(): array
    {
        $result = [];
        foreach ($this->translations as $translation) {
            $result[$translation->language] = $translation->translation;
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
}
