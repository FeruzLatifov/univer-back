<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * System Message Model
 *
 * Stores translatable message keys
 *
 * @property int $id
 * @property string|null $category
 * @property string $message
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<ESystemMessageTranslation> $translations
 */
class ESystemMessage extends Model
{
    protected $table = 'e_system_message';

    public $timestamps = false;

    protected $fillable = [
        'category',
        'message',
    ];

    /**
     * Get all translations for this message
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ESystemMessageTranslation::class, 'id');
    }

    /**
     * Get translation for specific language
     */
    public function getTranslation(string $language): ?string
    {
        return $this->translations()
            ->where('language', $language)
            ->value('translation');
    }

    /**
     * Get all translations as array [language => translation]
     */
    public function getAllTranslations(): array
    {
        return $this->translations()
            ->pluck('translation', 'language')
            ->toArray();
    }

    /**
     * Set translation for specific language
     */
    public function setTranslation(string $language, string $translation): void
    {
        ESystemMessageTranslation::updateOrCreate(
            [
                'id' => $this->id,
                'language' => $language,
            ],
            [
                'translation' => $translation,
            ]
        );
    }

    /**
     * Check if message has translation for language
     */
    public function hasTranslation(string $language): bool
    {
        return $this->translations()
            ->where('language', $language)
            ->exists();
    }
}
