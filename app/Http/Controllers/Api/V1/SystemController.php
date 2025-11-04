<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EUniversity;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    /**
     * Get login page configuration
     * Matches Yii2 backend/views/dashboard/login.php behavior
     */
    public function getLoginConfig(Request $request)
    {
        $language = $request->get('l', 'uz-UZ');

        // Get university from database (e_university table like Yii2)
        // Equivalent to EUniversity::findCurrentUniversity() in Yii2
        $university = EUniversity::getCurrent();

        // Compute logo with safe fallback if DB doesn't have a logo
        $logoPath = $university?->getLogo();
        if (empty($logoPath)) {
            $logoPath = env('UNIVERSITY_LOGO', '/images/hemis-logo.png');
        }
        $logo = $this->absoluteUrl($request, $logoPath);

        return response()->json([
            'success' => true,
            'data' => [
                'university_code' => $university?->code ?? null,
                'university_name' => $university?->name ?? env('UNIVERSITY_NAME', 'Universitet'),
                'university_short_name' => $university?->getShortName() ?? env('UNIVERSITY_SHORT_NAME', 'UNIVER'),
                'university_logo' => $logo,
                'favicon' => env('UNIVERSITY_FAVICON', '/favicon.ico'),
                'system_name' => env('SYSTEM_NAME', 'HEMIS Universitet axborot tizimi'),
                'app_version' => env('APP_VERSION', '1.0.0'),
                'login_types' => [
                    'employee' => [
                        'enabled' => true,
                        'label' => 'Xodim',
                        'icon' => 'employee',
                        'endpoint' => '/api/v1/employee/auth/login',
                    ],
                    'student' => [
                        'enabled' => true,
                        'label' => 'Talaba',
                        'icon' => 'student',
                        'endpoint' => '/api/v1/student/auth/login',
                    ],
                ],
                // Languages matching Yii2 Config::getLanguageOptions()
                'available_languages' => [
                    ['code' => 'uz-UZ', 'name' => 'O\'zbekcha', 'flag' => '🇺🇿', 'active' => true],
                    ['code' => 'oz-UZ', 'name' => 'Ўзбекча', 'flag' => '🇺🇿', 'active' => true],
                    ['code' => 'ru-RU', 'name' => 'Русский', 'flag' => '🇷🇺', 'active' => true],
                    ['code' => 'en-US', 'name' => 'English', 'flag' => '🇺🇸', 'active' => true],
                ],
                'default_language' => 'uz-UZ',
                'theme' => [
                    'primary_color' => '#1976d2',
                    'secondary_color' => '#dc004e',
                ],
                'features' => [
                    'registration_enabled' => false,
                    'password_reset_enabled' => true,
                    'remember_me_enabled' => true,
                ],
                'contact' => [
                    'email' => 'support@univer.uz',
                    'phone' => '+998 71 123 45 67',
                    'address' => 'Toshkent, O\'zbekiston',
                ],
            ],
        ]);
    }

    private function absoluteUrl(Request $request, string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        $prefix = rtrim($request->getSchemeAndHttpHost(), '/');
        $normalized = '/' . ltrim($path, '/');
        return $prefix . $normalized;
    }

    /**
     * Get translations for a specific language
     */
    public function getTranslations(Request $request, $language = 'uz-UZ')
    {
        return response()->json([
            'success' => true,
            'data' => [
                'language' => $language,
                'translations' => [],
            ],
        ]);
    }

    /**
     * Get list of available languages
     */
    public function getLanguages(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['code' => 'uz-UZ', 'name' => 'O\'zbekcha', 'flag' => '🇺🇿', 'active' => true],
                ['code' => 'ru-RU', 'name' => 'Русский', 'flag' => '🇷🇺', 'active' => true],
                ['code' => 'en-US', 'name' => 'English', 'flag' => '🇺🇸', 'active' => false],
            ],
        ]);
    }
}
