<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HUniversity extends Model
{
    protected $table = 'h_university';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'position',
        'active',
        '_translations',
        '_options'
    ];

    protected $casts = [
        'active' => 'boolean',
        '_options' => 'array',
        '_translations' => 'array',
        'position' => 'integer'
    ];

    /**
     * Get university name (translated)
     */
    public function getNameAttribute()
    {
        // Map language code to Yii2 format
        $langMap = [
            'uz' => 'uz',
            'uz-UZ' => 'uz',
            'ru' => 'ru',
            'ru-RU' => 'ru',
            'en' => 'en',
            'en-US' => 'en',
        ];

        $locale = request()->get('l', app()->getLocale());
        $lang = $langMap[$locale] ?? 'uz';

        // Get translation from _translations jsonb field
        $translations = $this->_translations ?? [];

        // Try to get translated name
        if (isset($translations["name_{$lang}"])) {
            return $translations["name_{$lang}"];
        }

        // Fallback to uzbek
        if (isset($translations["name_uz"])) {
            return $translations["name_uz"];
        }

        // Last fallback to 'name' column
        return $this->attributes['name'] ?? 'UNIVER';
    }

    /**
     * Get the current university (first active record)
     */
    public static function getCurrent()
    {
        return static::where('active', true)
            ->orderBy('position')
            ->first();
    }

    /**
     * Get university logo from _options
     */
    public function getLogo()
    {
        $options = $this->_options ?? [];
        return $options['logo'] ?? '/images/logo.png';
    }

    /**
     * Get university short name from _options or name
     */
    public function getShortName()
    {
        $options = $this->_options ?? [];
        return $options['short_name'] ?? $this->name ?? 'UNIVER';
    }
}
