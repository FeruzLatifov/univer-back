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
        // Get locale from Accept-Language header or query parameter
        $locale = $request->header('Accept-Language', $request->get('l', 'uz'));

        // Convert full locale to short (uz-UZ -> uz)
        $locale = substr($locale, 0, 2);

        // Set Laravel locale for trans() helper
        app()->setLocale($locale);

        // Get university from database (e_university table like Yii2)
        // Equivalent to EUniversity::findCurrentUniversity() in Yii2
        $university = EUniversity::getCurrent();

        // Priority: Database logo > ENV logo > null (frontend will use default)
        $logoPath = null;
        
        // 1. Check database first (bazadagi logo birinchi o'rinni egallaydi)
        if ($university) {
            $dbLogo = $university->getLogo();
            if ($dbLogo && $dbLogo !== '/images/logo.png') {
                $logoPath = $dbLogo;
            }
        }
        
        // 2. Fallback to ENV if no DB logo (bazada yo'q bo'lsa .env dan olinadi)
        if (!$logoPath) {
            $envLogo = env('UNIVERSITY_LOGO');
            if ($envLogo && $envLogo !== '/images/logo.png') {
                $logoPath = $envLogo;
            }
        }

        // 3. Convert to absolute URL if exists (agar mavjud bo'lsa to'liq URL ga o'giradi)
        $logo = $logoPath ? $this->absoluteUrl($request, $logoPath) : null;
        
        // 4. Favicon handling (favicon alohida)
        $faviconPath = env('UNIVERSITY_FAVICON', '/favicon.ico');
        $favicon = $this->absoluteUrl($request, $faviconPath);

        return response()->json([
            'success' => true,
            'data' => [
                'university_code' => $university?->code ?? null,
                'university_name' => $university?->name ?? env('UNIVERSITY_NAME', 'Universitet'),
                'university_short_name' => $university?->getShortName() ?? env('UNIVERSITY_SHORT_NAME', 'UNIVER'),
                'university_logo' => $logo,
                'favicon' => $favicon,
                'system_name' => env('SYSTEM_NAME', 'HEMIS Universitet axborot tizimi'),
                'app_version' => env('APP_VERSION', '1.0.0'),
                'login_types' => [
                    'employee' => [
                        'enabled' => true,
                        'label' => trans('roles.employee'),
                        'icon' => 'employee',
                        'endpoint' => '/api/v1/employee/auth/login',
                    ],
                    'student' => [
                        'enabled' => true,
                        'label' => trans('roles.student'),
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
        
        // Use APP_URL from .env (best practice for multi-environment support)
        $baseUrl = env('APP_URL', $request->getSchemeAndHttpHost());
        $prefix = rtrim($baseUrl, '/');
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
