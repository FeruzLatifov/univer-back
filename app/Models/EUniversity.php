<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EUniversity extends Model
{
    protected $table = 'e_university';
    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'name',
        'tin',
        'address',
        'contact',
        '_ownership',
        '_university_form',
        '_soato',
        '_translations',
        'mailing_address',
        'bank_details',
        'accreditation_info',
    ];

    protected $casts = [
        '_translations' => 'array',
        '_sync' => 'boolean',
        '_sync_diff' => 'array',
    ];

    /**
     * Get university name (translated)
     */
    public function getNameAttribute()
    {
        // Map language code to format
        $langMap = [
            'uz' => 'uz',
            'uz-UZ' => 'uz',
            'oz' => 'uz', // Cyrillic Uzbek uses same translations as Latin
            'oz-UZ' => 'uz',
            'ru' => 'ru',
            'ru-RU' => 'ru',
            'en' => 'en',
            'en-US' => 'en',
        ];

        // Get locale from X-Locale header, request param, or app locale
        $locale = request()->header('X-Locale')
            ?? request()->get('l')
            ?? app()->getLocale();
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
     * Get the current university (first record)
     * Matches Yii2 EUniversity::findCurrentUniversity()
     */
    public static function getCurrent()
    {
        return static::orderBy('id')->first();
    }

    /**
     * Get university logo from h_university table via code
     */
    public function getLogo()
    {
        $university = HUniversity::where('code', $this->code)->first();
        if ($university) {
            return $university->getLogo();
        }
        return '/images/logo.png';
    }

    /**
     * Get university short name
     */
    public function getShortName()
    {
        $university = HUniversity::where('code', $this->code)->first();
        if ($university) {
            return $university->getShortName();
        }
        return $this->name ?? 'UNIVER';
    }
}
