<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API So'rovlarini Tasdiqlash Middleware
 *
 * Bu middleware faqat ruxsat berilgan ilovalardan kelgan
 * so'rovlarni qabul qiladi. Boshqa frontend yoki mobile
 * ilovalar API'ga murojaat qila olmaydi.
 *
 * Tekshiruvlar:
 * 1. App Key mavjudligi va to'g'riligi
 * 2. So'rov imzosi (HMAC)
 * 3. Timestamp yangiligi (replay attack himoyasi)
 * 4. Origin tekshiruvi (web uchun)
 */
class VerifyAppSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Xavfsizlik o'chirilgan bo'lsa yoki development muhitida
        if (!config('api_security.enabled')) {
            return $next($request);
        }

        // Mustasno routelarni tekshirish
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // IP whitelist tekshirish
        if ($this->isWhitelistedIp($request)) {
            return $next($request);
        }

        // Bloklangan IP/qurilmani tekshirish
        if ($this->isBlocked($request)) {
            return $this->blockedResponse();
        }

        // App Key tekshirish
        $appKey = $request->header('X-App-Key');
        if (!$appKey) {
            $this->logFailedAttempt($request, 'Missing app key');
            return $this->unauthorizedResponse('App kaliti topilmadi');
        }

        // App konfiguratsiyasini olish
        $appConfig = $this->getAppConfig($appKey);
        if (!$appConfig) {
            $this->logFailedAttempt($request, 'Invalid app key');
            return $this->unauthorizedResponse('Noto\'g\'ri app kaliti');
        }

        // Web ilovalar uchun Origin tekshirish
        if (isset($appConfig['allowed_origins'])) {
            if (!$this->verifyOrigin($request, $appConfig['allowed_origins'])) {
                $this->logFailedAttempt($request, 'Invalid origin');
                return $this->unauthorizedResponse('Ruxsatsiz manba');
            }
        }

        // Timestamp tekshirish
        $timestamp = $request->header('X-App-Timestamp');
        if (!$this->verifyTimestamp($timestamp)) {
            $this->logFailedAttempt($request, 'Invalid timestamp');
            return $this->unauthorizedResponse('Vaqt belgisi noto\'g\'ri');
        }

        // Imzoni tekshirish
        $signature = $request->header('X-App-Signature');
        if (!$this->verifySignature($request, $appConfig['secret'], $signature)) {
            $this->logFailedAttempt($request, 'Invalid signature');
            return $this->unauthorizedResponse('Imzo noto\'g\'ri');
        }

        // Replay attack himoyasi - bir xil imzoni qayta ishlatmaslik
        if ($this->isReplayAttack($signature)) {
            $this->logFailedAttempt($request, 'Replay attack detected');
            return $this->unauthorizedResponse('Takroriy so\'rov aniqlandi');
        }

        // Imzoni cache'ga saqlash (replay himoyasi)
        $this->cacheSignature($signature);

        // Muvaffaqiyatli so'rovni log qilish (agar yoqilgan bo'lsa)
        if (config('api_security.security.log_requests')) {
            $this->logSuccessfulRequest($request, $appConfig['name']);
        }

        return $next($request);
    }

    /**
     * Mustasno routelarni tekshirish
     */
    protected function isExcludedRoute(Request $request): bool
    {
        $excludedRoutes = config('api_security.excluded_routes', []);
        $path = $request->path();

        foreach ($excludedRoutes as $route) {
            if (str_contains($route, '*')) {
                $pattern = str_replace('*', '.*', $route);
                if (preg_match("#^{$pattern}$#", $path)) {
                    return true;
                }
            } elseif ($path === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * IP whitelist tekshirish
     */
    protected function isWhitelistedIp(Request $request): bool
    {
        $whitelist = config('api_security.ip_whitelist', []);
        return !empty($whitelist) && in_array($request->ip(), $whitelist);
    }

    /**
     * Bloklangan IP/qurilmani tekshirish
     */
    protected function isBlocked(Request $request): bool
    {
        $key = 'api_blocked:' . $request->ip();
        return Cache::has($key);
    }

    /**
     * App konfiguratsiyasini key bo'yicha olish
     */
    protected function getAppConfig(string $appKey): ?array
    {
        $apps = config('api_security.app_keys', []);

        foreach ($apps as $app) {
            if (isset($app['key']) && $app['key'] === $appKey) {
                return $app;
            }
        }

        return null;
    }

    /**
     * Origin tekshirish
     */
    protected function verifyOrigin(Request $request, array $allowedOrigins): bool
    {
        $origin = $request->header('Origin') ?? $request->header('Referer');

        if (!$origin) {
            // Mobile app'lar Origin yubormasligi mumkin
            return true;
        }

        foreach ($allowedOrigins as $allowed) {
            if ($allowed && str_starts_with($origin, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Timestamp tekshirish
     */
    protected function verifyTimestamp(?string $timestamp): bool
    {
        if (!$timestamp || !is_numeric($timestamp)) {
            return false;
        }

        $tolerance = config('api_security.signature.timestamp_tolerance', 300);
        $currentTime = time();
        $requestTime = (int) $timestamp;

        return abs($currentTime - $requestTime) <= $tolerance;
    }

    /**
     * Imzoni tekshirish
     */
    protected function verifySignature(Request $request, string $secret, ?string $signature): bool
    {
        if (!$signature || !$secret) {
            return false;
        }

        $expectedSignature = $this->generateSignature($request, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Imzo yaratish
     */
    protected function generateSignature(Request $request, string $secret): string
    {
        $data = implode('|', [
            $request->header('X-App-Key'),
            $request->header('X-App-Timestamp'),
            $request->header('X-Device-Id', ''),
            $request->method(),
            $request->path(),
        ]);

        $algorithm = config('api_security.signature.algorithm', 'sha256');
        return hash_hmac($algorithm, $data, $secret);
    }

    /**
     * Replay attack tekshirish
     */
    protected function isReplayAttack(string $signature): bool
    {
        $key = 'api_signature:' . $signature;
        return Cache::has($key);
    }

    /**
     * Imzoni cache'ga saqlash
     */
    protected function cacheSignature(string $signature): void
    {
        $key = 'api_signature:' . $signature;
        $tolerance = config('api_security.signature.timestamp_tolerance', 300);
        Cache::put($key, true, $tolerance + 60);
    }

    /**
     * Muvaffaqiyatsiz urinishni log qilish
     */
    protected function logFailedAttempt(Request $request, string $reason): void
    {
        $key = 'api_failed:' . $request->ip();
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, 3600);

        // Maksimal urinishlardan oshsa bloklash
        $maxAttempts = config('api_security.security.max_failed_attempts', 10);
        if ($attempts >= $maxAttempts) {
            $blockDuration = config('api_security.security.block_duration', 3600);
            Cache::put('api_blocked:' . $request->ip(), true, $blockDuration);

            Log::warning('API Security: IP blocked due to too many failed attempts', [
                'ip' => $request->ip(),
                'attempts' => $attempts,
            ]);
        }

        Log::warning('API Security: Failed attempt', [
            'ip' => $request->ip(),
            'reason' => $reason,
            'path' => $request->path(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Muvaffaqiyatli so'rovni log qilish
     */
    protected function logSuccessfulRequest(Request $request, string $appName): void
    {
        Log::info('API Security: Successful request', [
            'app' => $appName,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);
    }

    /**
     * Ruxsatsiz javob
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Unauthorized app',
        ], 403);
    }

    /**
     * Bloklangan javob
     */
    protected function blockedResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Ko\'p muvaffaqiyatsiz urinishlar tufayli vaqtincha bloklangansiz',
            'error' => 'Blocked',
        ], 429);
    }
}
