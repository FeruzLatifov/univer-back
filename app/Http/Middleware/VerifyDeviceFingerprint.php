<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Device Fingerprint Middleware
 *
 * Qurilmalarni kuzatish va tasdiqlash uchun middleware.
 * Shubhali qurilmalarni aniqlash va bloklash.
 */
class VerifyDeviceFingerprint
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('api_security.enabled')) {
            return $next($request);
        }

        $deviceId = $request->header('X-Device-Id');
        $user = $request->user();

        // Autentifikatsiya qilinmagan so'rovlar uchun o'tkazib yuborish
        if (!$user) {
            return $next($request);
        }

        // Device ID mavjudligini tekshirish
        if (!$deviceId) {
            return $this->errorResponse('Qurilma identifikatori topilmadi');
        }

        // Qurilma ma'lumotlarini olish
        $deviceInfo = $this->getDeviceInfo($request, $deviceId);

        // Shubhali qurilmani tekshirish
        if ($this->isSuspiciousDevice($deviceInfo, $user->id)) {
            Log::warning('Suspicious device detected', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'ip' => $request->ip(),
            ]);

            if (config('api_security.device.block_suspicious')) {
                return $this->errorResponse('Shubhali qurilma aniqlandi');
            }
        }

        // Qurilma sessiyalarini tekshirish
        if (!$this->checkDeviceSessions($user->id, $deviceId)) {
            return $this->errorResponse('Maksimal qurilma limitiga yetdingiz');
        }

        // Qurilma faoliyatini yangilash
        $this->updateDeviceActivity($user->id, $deviceId, $deviceInfo);

        return $next($request);
    }

    /**
     * Qurilma ma'lumotlarini olish
     */
    protected function getDeviceInfo(Request $request, string $deviceId): array
    {
        return [
            'device_id' => $deviceId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'platform' => $this->detectPlatform($request->userAgent()),
            'last_seen' => now()->toDateTimeString(),
        ];
    }

    /**
     * Platformani aniqlash
     */
    protected function detectPlatform(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'android')) {
            return 'android';
        }
        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) {
            return 'ios';
        }
        if (str_contains($userAgent, 'windows')) {
            return 'windows';
        }
        if (str_contains($userAgent, 'macintosh') || str_contains($userAgent, 'mac os')) {
            return 'macos';
        }
        if (str_contains($userAgent, 'linux')) {
            return 'linux';
        }

        return 'web';
    }

    /**
     * Shubhali qurilmani tekshirish
     */
    protected function isSuspiciousDevice(array $deviceInfo, int $userId): bool
    {
        $key = "device_history:{$userId}:{$deviceInfo['device_id']}";
        $history = Cache::get($key);

        if (!$history) {
            return false;
        }

        // IP o'zgarishi
        if ($history['ip'] !== $deviceInfo['ip']) {
            // Tez-tez IP o'zgarishi shubhali
            $ipChanges = Cache::get("device_ip_changes:{$userId}:{$deviceInfo['device_id']}", 0);
            if ($ipChanges > 5) {
                return true;
            }
            Cache::put("device_ip_changes:{$userId}:{$deviceInfo['device_id']}", $ipChanges + 1, 3600);
        }

        // User agent o'zgarishi
        if ($history['user_agent'] !== $deviceInfo['user_agent']) {
            return true;
        }

        return false;
    }

    /**
     * Qurilma sessiyalarini tekshirish
     */
    protected function checkDeviceSessions(int $userId, string $deviceId): bool
    {
        $maxSessions = config('api_security.device.max_sessions_per_device', 3);
        $key = "user_devices:{$userId}";

        $devices = Cache::get($key, []);

        // Yangi qurilma bo'lsa va limit to'lgan bo'lsa
        if (!isset($devices[$deviceId]) && count($devices) >= $maxSessions) {
            return false;
        }

        return true;
    }

    /**
     * Qurilma faoliyatini yangilash
     */
    protected function updateDeviceActivity(int $userId, string $deviceId, array $deviceInfo): void
    {
        // Qurilma tarixini saqlash
        $historyKey = "device_history:{$userId}:{$deviceId}";
        Cache::put($historyKey, $deviceInfo, 86400 * 30); // 30 kun

        // Foydalanuvchi qurilmalarini yangilash
        $devicesKey = "user_devices:{$userId}";
        $devices = Cache::get($devicesKey, []);
        $devices[$deviceId] = [
            'last_seen' => now()->toDateTimeString(),
            'platform' => $deviceInfo['platform'],
        ];
        Cache::put($devicesKey, $devices, 86400 * 30); // 30 kun
    }

    /**
     * Xato javobi
     */
    protected function errorResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Device verification failed',
        ], 403);
    }
}
