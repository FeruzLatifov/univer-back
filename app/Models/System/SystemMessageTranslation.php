<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * System message translations model
 *
 * Structure:
 * - translation: Base translation (from CSV/Git imports)
 * - custom_translation: University-specific override
 * 
 * Priority: custom_translation > translation
 *
 * @property int $id
 * @property string $language
 * @property string|null $translation (base)
 * @property string|null $custom_translation (override)
 * @property SystemMessage $message
 */
class SystemMessageTranslation extends Model
{
    protected $table = 'e_system_message_translation';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['id', 'language'];

    protected $fillable = [
        'id',
        'language',
        'translation',
        'custom_translation',
    ];

    /**
     * Relationship to message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SystemMessage::class, 'id', 'id');
    }

    /**
     * Get final translation (custom > base)
     */
    public function getFinalTranslation(): ?string
    {
        return $this->custom_translation ?? $this->translation;
    }

    /**
     * Check if has custom translation
     */
    public function hasCustomTranslation(): bool
    {
        return !empty($this->custom_translation);
    }
}
