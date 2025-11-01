<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * HLanguage Model
 *
 * @property string $code
 * @property string $name
 * @property string|null $native_name
 * @property int $position
 * @property bool $active
 * @property string|null $_parent
 * @property array|null $_translations
 * @property array|null $_options
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HLanguage extends Model
{
    protected $table = 'h_language';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'active' => 'boolean',
        'position' => 'integer',
        '_translations' => 'array',
        '_options' => 'array',
    ];

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
     */
    public static function getActiveLanguages(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'position' => $lang->position,
            ])
            ->toArray();
    }
}
