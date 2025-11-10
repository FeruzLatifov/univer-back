<?php

namespace App\Services;

/**
 * Language Mapper Service
 * 
 * Maps Yii2 INTEGER language codes to ISO string codes
 * Prevents touching Yii2 database structure
 */
class LanguageMapper
{
    /**
     * Yii2 INTEGER code → ISO string code mapping
     * Based on real hemis_401 database
     */
    private const MAP = [
        11 => 'uz',  // O'zbek
        12 => 'ru',  // Rus  
        13 => 'oz',  // Qoraqalpoq (Ўзбекча)
        14 => 'en',  // Ingliz
        15 => 'tj',  // Tojik
        16 => 'kz',  // Qozoq
        17 => 'tm',  // Turkman
        18 => 'ko',  // Koreys
        19 => 'de',  // Nemis
        20 => 'fr',  // Frantsuz
    ];

    /**
     * Reverse map: ISO → Yii2 INTEGER
     */
    private const REVERSE_MAP = [
        'uz' => 11,
        'ru' => 12,
        'oz' => 13,
        'en' => 14,
        'tj' => 15,
        'kz' => 16,
        'tm' => 17,
        'ko' => 18,
        'de' => 19,
        'fr' => 20,
    ];

    /**
     * Language native names
     */
    private const NATIVE_NAMES = [
        'uz' => "O'zbekcha (lotin)",
        'oz' => "Ўзбекча (kirill)",
        'ru' => 'Русский',
        'en' => 'English',
        'tj' => 'Тоҷикӣ',
        'kz' => 'Қазақша',
        'tm' => 'Türkmençe',
        'ko' => '한국어',
        'de' => 'Deutsch',
        'fr' => 'Français',
    ];

    /**
     * Convert Yii2 INTEGER code to ISO string code
     * 
     * @param int $yiiCode Yii2 language code (11, 12, ...)
     * @return string|null ISO code ('uz', 'ru', ...) or null if not found
     */
    public static function toIso(int $yiiCode): ?string
    {
        return self::MAP[$yiiCode] ?? null;
    }

    /**
     * Convert ISO string code to Yii2 INTEGER code
     * 
     * @param string $isoCode ISO language code ('uz', 'ru', ...)
     * @return int|null Yii2 code (11, 12, ...) or null if not found
     */
    public static function toYii(string $isoCode): ?int
    {
        return self::REVERSE_MAP[$isoCode] ?? null;
    }

    /**
     * Get native name for ISO code
     * 
     * @param string|null $isoCode ISO language code
     * @return string Native language name
     */
    public static function getNativeName(?string $isoCode): string
    {
        if ($isoCode === null) {
            return '';
        }
        
        return self::NATIVE_NAMES[$isoCode] ?? $isoCode;
    }

    /**
     * Get all available ISO codes
     * 
     * @return array<string>
     */
    public static function getAllIsoCodes(): array
    {
        return array_keys(self::REVERSE_MAP);
    }

    /**
     * Check if ISO code is valid
     * 
     * @param string $isoCode
     * @return bool
     */
    public static function isValidIsoCode(string $isoCode): bool
    {
        return isset(self::REVERSE_MAP[$isoCode]);
    }

    /**
     * Check if Yii2 code is valid
     * 
     * @param int $yiiCode
     * @return bool
     */
    public static function isValidYiiCode(int $yiiCode): bool
    {
        return isset(self::MAP[$yiiCode]);
    }
}
