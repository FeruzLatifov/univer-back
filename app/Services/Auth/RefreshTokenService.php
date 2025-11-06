<?php

namespace App\Services\Auth;

use App\Models\AuthRefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function __construct(
        private ?int $ttlMinutes = null
    ) {
        $this->ttlMinutes = $ttlMinutes ?? (int) env('AUTH_REFRESH_TTL_MINUTES', 43200); // 30 days
    }

    public function createForUser(int $userId, string $userType, string $ip, ?string $userAgent = null): string
    {
        $this->purgeExpired();
        $this->revokeAllForUser($userId, $userType);

        $plainToken = Str::random(64);

        AuthRefreshToken::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'token_hash' => $this->hashToken($plainToken),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => Carbon::now()->addMinutes($this->ttlMinutes),
        ]);

        return $plainToken;
    }

    public function findValid(string $token, string $userType): ?AuthRefreshToken
    {
        if (empty($token)) {
            return null;
        }

        return AuthRefreshToken::where('user_type', $userType)
            ->where('token_hash', $this->hashToken($token))
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function rotate(AuthRefreshToken $record, string $ip, ?string $userAgent = null): string
    {
        $this->purgeExpired();
        $userId = $record->user_id;
        $userType = $record->user_type;
        $record->delete();

        return $this->createNewRecord($userId, $userType, $ip, $userAgent);
    }

    public function revokeByToken(?string $token, string $userType): void
    {
        if (!$token) {
            return;
        }

        AuthRefreshToken::where('user_type', $userType)
            ->where('token_hash', $this->hashToken($token))
            ->delete();
    }

    public function revokeAllForUser(int $userId, string $userType): void
    {
        AuthRefreshToken::where('user_id', $userId)
            ->where('user_type', $userType)
            ->delete();
    }

    public function purgeExpired(): void
    {
        AuthRefreshToken::where('expires_at', '<=', Carbon::now())->delete();
    }

    private function createNewRecord(int $userId, string $userType, string $ip, ?string $userAgent = null): string
    {
        $plainToken = Str::random(64);

        AuthRefreshToken::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'token_hash' => $this->hashToken($plainToken),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'expires_at' => Carbon::now()->addMinutes($this->ttlMinutes),
        ]);

        return $plainToken;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
