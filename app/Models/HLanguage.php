<?php

namespace App\Models;

use App\Services\LanguageMapper;
use Illuminate\Database\Eloquent\Model;

/**
 * HLanguage Model
 *
 * @property int $code Yii2 INTEGER code (11, 12, ...)
 * @property string $name
 * @property string|null $native_name
 * @property int $position
 * @property bool $active
 * @property string|null $_parent
 * @property array|null $_translations
 * @property array|null $_options
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read string $iso_code ISO string code (uz, ru, ...)
 */
class HLanguage extends Model
{
    protected $table = 'h_language';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'position',
        'active',
        '_parent',
        '_translations',
        '_options',
    ];

    protected $casts = [
        'code' => 'integer',
        'active' => 'boolean',
        'position' => 'integer',
        '_translations' => 'array',
        '_options' => 'array',
    ];

    /**
     * Appends: virtual attributes
     */
    protected $appends = ['iso_code'];

    /**
     * Scope: Active languages
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Virtual Attribute: ISO code
     * Converts Yii2 INTEGER code to ISO string code
     * 
     * @return string|null
     */
    public function getIsoCodeAttribute(): ?string
    {
        return LanguageMapper::toIso($this->code);
    }

    /**
     * Get translated name
     */
    public function getTranslatedName(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->_translations && isset($this->_translations[$locale]['name'])) {
            return $this->_translations[$locale]['name'];
        }

        return $this->name;
    }

    /**
     * Get parent language
     */
    public function parent()
    {
        return $this->belongsTo(HLanguage::class, '_parent', 'code');
    }

    /**
     * Get child languages
     */
    public function children()
    {
        return $this->hasMany(HLanguage::class, '_parent', 'code');
    }

    /**
     * Get all active languages ordered by position
     * Returns ISO codes (uz, ru, ...) instead of Yii2 INTEGER codes
     */
    public static function getActiveLanguages(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn($lang) => [
                'code' => $lang->iso_code,  // ISO code
                'yii_code' => $lang->code,  // Original INTEGER code
                'name' => $lang->name,
                'native_name' => $lang->native_name ?: LanguageMapper::getNativeName($lang->iso_code),
                'position' => $lang->position,
            ])
            ->filter(fn($lang) => $lang['code'] !== null)  // Remove unmapped languages
            ->values()
            ->toArray();
    }
}
