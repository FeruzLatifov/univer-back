<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OAuth\OAuthService;
use App\Models\OAuthClient;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;
use App\Models\OAuthAuthCode;
use App\Models\EEmployee;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OAuthServiceTest extends TestCase
{
    private OAuthService $oauthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oauthService = new OAuthService();
    }

    /**
     * Test generate authorization code
     */
    public function test_generate_authorization_code(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $authCode = $this->oauthService->generateAuthorizationCode(
            $client->id,
            $employee->id
        );

        $this->assertInstanceOf(OAuthAuthCode::class, $authCode);
        $this->assertEquals($client->id, $authCode->_client);
        $this->assertEquals($employee->id, $authCode->_user);
        $this->assertFalse($authCode->revoked);
        $this->assertTrue($authCode->expires_at->isFuture());
    }

    /**
     * Test generate authorization code with scopes
     */
    public function test_generate_authorization_code_with_scopes(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $scopes = ['read', 'write'];
        $authCode = $this->oauthService->generateAuthorizationCode(
            $client->id,
            $employee->id,
            $scopes
        );

        $this->assertCount(count($scopes), $authCode->scopes);
    }

    /**
     * Test exchange authorization code for tokens
     */
    public function test_exchange_authorization_code(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Create auth code
        $authCode = $this->oauthService->generateAuthorizationCode(
            $client->id,
            $employee->id
        );

        // Exchange it
        $tokens = $this->oauthService->exchangeAuthorizationCode(
            $authCode->id,
            $client->id
        );

        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('token_type', $tokens);
        $this->assertArrayHasKey('expires_in', $tokens);
        $this->assertEquals('Bearer', $tokens['token_type']);

        // Verify auth code is revoked
        $authCode->refresh();
        $this->assertTrue($authCode->revoked);
    }

    /**
     * Test exchange authorization code fails with invalid code
     */
    public function test_exchange_authorization_code_fails_with_invalid_code(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid authorization code');

        $this->oauthService->exchangeAuthorizationCode(
            'invalid-code-123',
            1
        );
    }

    /**
     * Test exchange authorization code fails with client mismatch
     */
    public function test_exchange_authorization_code_fails_with_client_mismatch(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $authCode = $this->oauthService->generateAuthorizationCode(
            $client->id,
            $employee->id
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Client mismatch');

        $this->oauthService->exchangeAuthorizationCode(
            $authCode->id,
            999999 // Different client ID
        );
    }

    /**
     * Test exchange authorization code fails when already used
     */
    public function test_exchange_authorization_code_fails_when_already_used(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $authCode = $this->oauthService->generateAuthorizationCode(
            $client->id,
            $employee->id
        );

        // First exchange - should work
        $tokens = $this->oauthService->exchangeAuthorizationCode(
            $authCode->id,
            $client->id
        );
        $this->assertIsArray($tokens);

        // Second exchange - should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authorization code already used');

        $this->oauthService->exchangeAuthorizationCode(
            $authCode->id,
            $client->id
        );
    }

    /**
     * Test generate access token
     */
    public function test_generate_access_token(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );

        $this->assertInstanceOf(OAuthAccessToken::class, $accessToken);
        $this->assertEquals($client->id, $accessToken->_client);
        $this->assertEquals($employee->id, $accessToken->_user);
        $this->assertFalse($accessToken->revoked);
        $this->assertTrue($accessToken->expires_at->isFuture());
    }

    /**
     * Test generate refresh token
     */
    public function test_generate_refresh_token(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );

        $refreshToken = $this->oauthService->generateRefreshToken($accessToken->id);

        $this->assertInstanceOf(OAuthRefreshToken::class, $refreshToken);
        $this->assertFalse($refreshToken->revoked);
        $this->assertTrue($refreshToken->expires_at->isFuture());
    }

    /**
     * Test refresh access token
     */
    public function test_refresh_access_token(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Create initial tokens
        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );
        $refreshToken = $this->oauthService->generateRefreshToken($accessToken->id);

        // Refresh
        $newTokens = $this->oauthService->refreshAccessToken(
            $refreshToken->id,
            $client->id
        );

        $this->assertIsArray($newTokens);
        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertArrayHasKey('refresh_token', $newTokens);
        $this->assertNotEquals($accessToken->id, $newTokens['access_token']);
        $this->assertNotEquals($refreshToken->id, $newTokens['refresh_token']);

        // Verify old tokens are revoked
        $accessToken->refresh();
        $refreshToken->refresh();
        $this->assertTrue($accessToken->revoked);
        $this->assertTrue($refreshToken->revoked);
    }

    /**
     * Test refresh access token fails with invalid refresh token
     */
    public function test_refresh_access_token_fails_with_invalid_token(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->oauthService->refreshAccessToken(
            'invalid-refresh-token-123',
            1
        );
    }

    /**
     * Test validate access token
     */
    public function test_validate_access_token(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );

        $validatedToken = $this->oauthService->validateAccessToken($accessToken->id);

        $this->assertInstanceOf(OAuthAccessToken::class, $validatedToken);
        $this->assertEquals($accessToken->id, $validatedToken->id);
    }

    /**
     * Test validate access token returns null for invalid token
     */
    public function test_validate_access_token_returns_null_for_invalid(): void
    {
        $result = $this->oauthService->validateAccessToken('invalid-token-123');

        $this->assertNull($result);
    }

    /**
     * Test validate access token returns null for revoked token
     */
    public function test_validate_access_token_returns_null_for_revoked(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );

        // Revoke it
        $accessToken->revoke();

        // Try to validate
        $result = $this->oauthService->validateAccessToken($accessToken->id);

        $this->assertNull($result);
    }

    /**
     * Test revoke access token
     */
    public function test_revoke_access_token(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        $accessToken = $this->oauthService->generateAccessToken(
            $client->id,
            $employee->id
        );

        $result = $this->oauthService->revokeAccessToken($accessToken->id);

        $this->assertTrue($result);

        // Verify token is revoked
        $accessToken->refresh();
        $this->assertTrue($accessToken->revoked);
    }

    /**
     * Test revoke access token returns false for invalid token
     */
    public function test_revoke_access_token_returns_false_for_invalid(): void
    {
        $result = $this->oauthService->revokeAccessToken('invalid-token-123');

        $this->assertFalse($result);
    }

    /**
     * Test validate client
     */
    public function test_validate_client(): void
    {
        $client = OAuthClient::where('revoked', false)->first();

        if (!$client) {
            $this->markTestSkipped('No active OAuth client found in database');
        }

        $result = $this->oauthService->validateClient($client->id);

        $this->assertTrue($result);
    }

    /**
     * Test validate client fails for revoked client
     */
    public function test_validate_client_fails_for_revoked(): void
    {
        $client = OAuthClient::where('revoked', true)->first();

        if (!$client) {
            $this->markTestSkipped('No revoked OAuth client found in database');
        }

        $result = $this->oauthService->validateClient($client->id);

        $this->assertFalse($result);
    }

    /**
     * Test validate client fails for non-existent client
     */
    public function test_validate_client_fails_for_nonexistent(): void
    {
        $result = $this->oauthService->validateClient(999999);

        $this->assertFalse($result);
    }

    /**
     * Test get client
     */
    public function test_get_client(): void
    {
        $client = OAuthClient::first();

        if (!$client) {
            $this->markTestSkipped('No OAuth client found in database');
        }

        $result = $this->oauthService->getClient($client->id);

        $this->assertInstanceOf(OAuthClient::class, $result);
        $this->assertEquals($client->id, $result->id);
    }

    /**
     * Test get client returns null for non-existent
     */
    public function test_get_client_returns_null_for_nonexistent(): void
    {
        $result = $this->oauthService->getClient(999999);

        $this->assertNull($result);
    }
}
