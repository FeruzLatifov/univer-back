<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

/**
 * Translatable Trait
 *
 * Provides translation functionality for Eloquent models using JSONB _translations column
 *
 * Usage:
 *   use Translatable;
 *   protected $translatable = ['name', 'description'];
 *
 * Then access:
 *   $model->name         // Returns translation for current locale or original
 *   $model->name_uz      // Returns Uzbek translation
 *   $model->name_ru      // Returns Russian translation
 *   $model->translate('name', 'en')  // Returns English translation
 */
trait Translatable
{
    /**
     * Boot the translatable trait
     */
    public static function bootTranslatable()
    {
        // Cast _translations to array automatically
        static::retrieved(function ($model) {
            if (!isset($model->casts['_translations'])) {
                $model->casts = array_merge($model->casts ?? [], ['_translations' => 'array']);
            }
        });
    }

    /**
     * Get translatable attributes
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Get translation for a field
     *
     * @param string $field Field name (e.g., 'name')
     * @param string|null $locale Locale code (e.g., 'uz', 'ru', 'en')
     * @return string|null
     */
    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();

        // Check if _translations exists and has the field translation
        if ($this->_translations && isset($this->_translations[$locale][$field])) {
            return $this->_translations[$locale][$field];
        }

        // Fallback to original value
        return $this->getOriginal($field);
    }

    /**
     * Set translation for a field
     *
     * @param string $field Field name
     * @param string $locale Locale code
     * @param mixed $value Translation value
     * @return self
     */
    public function setTranslation(string $field, string $locale, $value): self
    {
        $translations = $this->_translations ?? [];

        if (!isset($translations[$locale])) {
            $translations[$locale] = [];
        }

        $translations[$locale][$field] = $value;
        $this->_translations = $translations;

        return $this;
    }

    /**
     * Set multiple translations at once
     *
     * @param array $translations ['uz' => ['name' => 'Test'], 'ru' => ['name' => 'Тест']]
     * @return self
     */
    public function setTranslations(array $translations): self
    {
        $this->_translations = array_merge($this->_translations ?? [], $translations);
        return $this;
    }

    /**
     * Get attribute with automatic translation
     *
     * Override getAttribute to provide automatic translation
     */
    public function getAttribute($key)
    {
        // Check if it's a translatable field with locale suffix (e.g., name_uz)
        if (preg_match('/^(.+)_(uz|ru|en)$/', $key, $matches)) {
            $field = $matches[1];
            $locale = $matches[2];

            if (in_array($field, $this->getTranslatableAttributes())) {
                return $this->translate($field, $locale);
            }
        }

        // Check if it's a translatable field without suffix
        if (in_array($key, $this->getTranslatableAttributes())) {
            return $this->translate($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Convert model to array with translations
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Add translated fields for current locale
        foreach ($this->getTranslatableAttributes() as $field) {
            if (isset($array[$field])) {
                $array[$field] = $this->translate($field);
            }

            // Also add field_uz, field_ru, field_en for all locales
            foreach (['uz', 'ru', 'en'] as $locale) {
                $array["{$field}_{$locale}"] = $this->translate($field, $locale);
            }
        }

        return $array;
    }

    /**
     * Get all translations for a field
     *
     * @param string $field Field name
     * @return array ['uz' => 'value', 'ru' => 'value', 'en' => 'value']
     */
    public function getTranslations(string $field): array
    {
        $translations = [];

        foreach (['uz', 'ru', 'en'] as $locale) {
            $translations[$locale] = $this->translate($field, $locale);
        }

        return $translations;
    }

    /**
     * Check if translation exists for a field
     */
    public function hasTranslation(string $field, string $locale): bool
    {
        return isset($this->_translations[$locale][$field]);
    }

    /**
     * Scope: Filter by translated field
     */
    public function scopeWhereTranslation($query, string $field, string $value, ?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();

        return $query->whereRaw("_translations->'{$locale}'->'{$field}' = ?", [$value]);
    }

    /**
     * Scope: Search in translated field
     */
    public function scopeWhereTranslationLike($query, string $field, string $value, ?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();

        return $query->whereRaw("_translations->'{$locale}'->'{$field}' ILIKE ?", ["%{$value}%"]);
    }
}
