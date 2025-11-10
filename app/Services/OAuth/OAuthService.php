<?php

namespace App\Services\OAuth;

use App\Models\OAuthClient;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthScope;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * OAuth2 Service
 *
 * Implements OAuth2 Authorization Code Flow
 * Compatible with Yii2 OAuth2 implementation
 */
class OAuthService
{
    // Token TTL (in minutes)
    const ACCESS_TOKEN_TTL = 3600; // 60 minutes
    const REFRESH_TOKEN_TTL = 43200; // 30 days
    const AUTH_CODE_TTL = 10; // 10 minutes

    /**
     * Generate authorization code
     */
    public function generateAuthorizationCode(
        int $clientId,
        int $userId,
        ?array $scopes = null
    ): OAuthAuthCode {
        $code = Str::random(40);

        $authCode = OAuthAuthCode::create([
            'id' => $code,
            '_client' => $clientId,
            '_user' => $userId,
            'expires_at' => Carbon::now()->addMinutes(self::AUTH_CODE_TTL),
            'revoked' => false,
        ]);

        // Attach scopes if provided
        if ($scopes) {
            $authCode->scopes()->attach($scopes);
        }

        return $authCode;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeAuthorizationCode(
        string $code,
        int $clientId,
        ?string $clientSecret = null
    ): array {
        // Find and validate auth code
        $authCode = OAuthAuthCode::with(['client', 'scopes'])
            ->find($code);

        if (!$authCode) {
            throw new \Exception('Invalid authorization code');
        }

        // Validate client
        if ($authCode->_client !== $clientId) {
            throw new \Exception('Client mismatch');
        }

        // Validate client secret if provided
        if ($clientSecret && $authCode->client->secret) {
            if (!Hash::check($clientSecret, $authCode->client->secret)) {
                throw new \Exception('Invalid client secret');
            }
        }

        // Check if code is expired
        if ($authCode->isExpired()) {
            throw new \Exception('Authorization code expired');
        }

        // Check if code is revoked
        if ($authCode->isRevoked()) {
            throw new \Exception('Authorization code already used');
        }

        // Generate tokens
        $accessToken = $this->generateAccessToken(
            $clientId,
            $authCode->_user,
            $authCode->scopes->pluck('id')->toArray()
        );

        $refreshToken = $this->generateRefreshToken($accessToken->id);

        // Revoke auth code (one-time use)
        $authCode->revoke();

        return [
            'access_token' => $accessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => $accessToken->expiresIn(),
            'refresh_token' => $refreshToken->id,
        ];
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(
        int $clientId,
        ?int $userId = null,
        ?array $scopes = null
    ): OAuthAccessToken {
        $token = Str::random(40);

        $accessToken = OAuthAccessToken::create([
            'id' => $token,
            '_client' => $clientId,
            '_user' => $userId,
            'expires_at' => Carbon::now()->addMinutes(self::ACCESS_TOKEN_TTL),
            'revoked' => false,
        ]);

        // Attach scopes if provided
        if ($scopes) {
            $accessToken->scopes()->attach($scopes);
        }

        return $accessToken;
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(string $accessTokenId): OAuthRefreshToken
    {
        $token = Str::random(40);

        return OAuthRefreshToken::create([
            'id' => $token,
            '_access_token' => (int) $accessTokenId, // Note: Yii2 uses integer, but token ID is string
            'expires_at' => Carbon::now()->addMinutes(self::REFRESH_TOKEN_TTL),
            'revoked' => false,
        ]);
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(
        string $refreshTokenId,
        int $clientId
    ): array {
        // Find refresh token
        $refreshToken = OAuthRefreshToken::find($refreshTokenId);

        if (!$refreshToken) {
            throw new \Exception('Invalid refresh token');
        }

        // Check if expired
        if ($refreshToken->isExpired()) {
            throw new \Exception('Refresh token expired');
        }

        // Check if revoked
        if ($refreshToken->isRevoked()) {
            throw new \Exception('Refresh token revoked');
        }

        // Get old access token to copy user and scopes
        $oldAccessToken = OAuthAccessToken::with('scopes')
            ->find($refreshToken->_access_token);

        if (!$oldAccessToken) {
            throw new \Exception('Original access token not found');
        }

        // Validate client
        if ($oldAccessToken->_client !== $clientId) {
            throw new \Exception('Client mismatch');
        }

        // Generate new tokens
        $newAccessToken = $this->generateAccessToken(
            $clientId,
            $oldAccessToken->_user,
            $oldAccessToken->scopes->pluck('id')->toArray()
        );

        $newRefreshToken = $this->generateRefreshToken($newAccessToken->id);

        // Revoke old tokens
        $oldAccessToken->revoke();
        $refreshToken->revoke();

        return [
            'access_token' => $newAccessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => $newAccessToken->expiresIn(),
            'refresh_token' => $newRefreshToken->id,
        ];
    }

    /**
     * Validate access token
     */
    public function validateAccessToken(string $token): ?OAuthAccessToken
    {
        $accessToken = OAuthAccessToken::with(['client', 'user', 'scopes'])
            ->find($token);

        if (!$accessToken) {
            return null;
        }

        if (!$accessToken->isValid()) {
            return null;
        }

        return $accessToken;
    }

    /**
     * Revoke access token
     */
    public function revokeAccessToken(string $token): bool
    {
        $accessToken = OAuthAccessToken::find($token);

        if (!$accessToken) {
            return false;
        }

        return $accessToken->revoke();
    }

    /**
     * Create OAuth client
     */
    public function createClient(
        string $name,
        int $userId,
        string $redirect,
        int $grantType = 1,
        int $tokenType = 1,
        ?string $secret = null
    ): OAuthClient {
        // Hash secret if provided
        if ($secret) {
            $secret = Hash::make($secret);
        }

        return OAuthClient::create([
            'name' => $name,
            '_user' => $userId,
            'redirect' => $redirect,
            'grant_type' => $grantType,
            'token_type' => $tokenType,
            'secret' => $secret,
            'revoked' => false,
        ]);
    }

    /**
     * Get client by ID
     */
    public function getClient(int $clientId): ?OAuthClient
    {
        return OAuthClient::find($clientId);
    }

    /**
     * Validate client credentials
     */
    public function validateClient(int $clientId, ?string $clientSecret = null): bool
    {
        $client = $this->getClient($clientId);

        if (!$client) {
            return false;
        }

        if ($client->isRevoked()) {
            return false;
        }

        // If client has secret, verify it
        if ($client->secret && $clientSecret) {
            return Hash::check($clientSecret, $client->secret);
        }

        // No secret required
        return true;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $count = 0;

        // Delete expired access tokens
        $count += OAuthAccessToken::expired()->delete();

        // Delete expired refresh tokens
        $count += OAuthRefreshToken::expired()->delete();

        // Delete expired auth codes
        $count += OAuthAuthCode::expired()->delete();

        return $count;
    }
}
