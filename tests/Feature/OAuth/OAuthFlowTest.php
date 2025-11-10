<?php

namespace Tests\Feature\OAuth;

use Tests\TestCase;
use App\Models\OAuthClient;
use App\Models\EEmployee;
use App\Models\OAuthScope;
use App\Services\OAuth\OAuthService;

class OAuthFlowTest extends TestCase
{

    /**
     * Test authorization endpoint with valid client
     */
    public function test_authorization_endpoint_with_valid_client(): void
    {
        $client = OAuthClient::where('revoked', false)->first();

        if (!$client) {
            $this->markTestSkipped('No active OAuth client found in database');
        }

        $response = $this->getJson('/api/v1/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'response_type' => 'code',
            'state' => 'test-state-123',
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'client' => ['id', 'name'],
                    'redirect_uri',
                    'state',
                    'message',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'client' => [
                        'id' => $client->id,
                    ],
                ],
            ]);
    }

    /**
     * Test authorization with invalid client ID
     */
    public function test_authorization_with_invalid_client_id(): void
    {
        $response = $this->getJson('/api/v1/oauth/authorize?' . http_build_query([
            'client_id' => 999999,
            'redirect_uri' => 'https://example.com/callback',
            'response_type' => 'code',
        ]));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test authorization with revoked client
     */
    public function test_authorization_with_revoked_client(): void
    {
        $client = OAuthClient::where('revoked', true)->first();

        if (!$client) {
            $this->markTestSkipped('No revoked OAuth client found in database');
        }

        $response = $this->getJson('/api/v1/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'response_type' => 'code',
        ]));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or revoked client',
            ]);
    }

    /**
     * Test authorization with redirect URI mismatch
     */
    public function test_authorization_with_redirect_uri_mismatch(): void
    {
        $client = OAuthClient::where('revoked', false)->first();

        if (!$client) {
            $this->markTestSkipped('No active OAuth client found in database');
        }

        $response = $this->getJson('/api/v1/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'https://malicious.com/callback',
            'response_type' => 'code',
        ]));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Redirect URI mismatch',
            ]);
    }

    /**
     * Test grant authorization (requires authentication)
     */
    public function test_grant_authorization_requires_authentication(): void
    {
        $client = OAuthClient::where('revoked', false)->first();

        if (!$client) {
            $this->markTestSkipped('No active OAuth client found in database');
        }

        $response = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authentication required',
            ]);
    }

    /**
     * Test complete authorization code flow
     */
    public function test_complete_authorization_code_flow(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data (client or employee)');
        }

        // Step 1: Login employee to get access token
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $employee->login,
            'password' => 'admin123',
        ]);
        $loginResponse->assertStatus(200);
        $employeeToken = $loginResponse->json('data.access_token');

        // Step 2: Grant authorization (user approves client)
        $grantResponse = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'state' => 'test-state-123',
        ], [
            'Authorization' => 'Bearer ' . $employeeToken,
        ]);

        $grantResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'redirect_url',
                    'code',
                    'expires_in',
                ],
            ]);

        $authCode = $grantResponse->json('data.code');

        // Step 3: Exchange authorization code for access token
        $tokenResponse = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);

        $tokenResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);

        $accessToken = $tokenResponse->json('access_token');
        $refreshToken = $tokenResponse->json('refresh_token');

        // Step 4: Use access token to get user info
        $userinfoResponse = $this->getJson('/api/v1/oauth/userinfo?access_token=' . $accessToken);

        $userinfoResponse->assertStatus(200)
            ->assertJsonStructure([
                'sub',
                'client_id',
                'scopes',
                'exp',
            ])
            ->assertJson([
                'sub' => $employee->id,
                'client_id' => $client->id,
            ]);
    }

    /**
     * Test authorization code can only be used once
     */
    public function test_authorization_code_can_only_be_used_once(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Login and grant authorization
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $employee->login,
            'password' => 'admin123',
        ]);
        $employeeToken = $loginResponse->json('data.access_token');

        $grantResponse = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
        ], [
            'Authorization' => 'Bearer ' . $employeeToken,
        ]);

        $authCode = $grantResponse->json('data.code');

        // First exchange - should work
        $firstToken = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);
        $firstToken->assertStatus(200);

        // Second exchange - should fail
        $secondToken = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);
        $secondToken->assertStatus(400)
            ->assertJsonStructure(['error', 'error_description']);
    }

    /**
     * Test refresh token grant
     */
    public function test_refresh_token_grant(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Get initial tokens through auth code flow
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $employee->login,
            'password' => 'admin123',
        ]);
        $employeeToken = $loginResponse->json('data.access_token');

        $grantResponse = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
        ], [
            'Authorization' => 'Bearer ' . $employeeToken,
        ]);

        $authCode = $grantResponse->json('data.code');

        $tokenResponse = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);

        $refreshToken = $tokenResponse->json('refresh_token');

        // Use refresh token to get new access token
        $refreshResponse = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'refresh_token' => $refreshToken,
        ]);

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
            ]);

        // Verify new tokens are different
        $this->assertNotEquals(
            $refreshToken,
            $refreshResponse->json('refresh_token'),
            'Refresh token should be rotated'
        );
    }

    /**
     * Test refresh token can only be used once
     */
    public function test_refresh_token_can_only_be_used_once(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Get initial tokens
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $employee->login,
            'password' => 'admin123',
        ]);
        $employeeToken = $loginResponse->json('data.access_token');

        $grantResponse = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
        ], [
            'Authorization' => 'Bearer ' . $employeeToken,
        ]);

        $authCode = $grantResponse->json('data.code');

        $tokenResponse = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);

        $refreshToken = $tokenResponse->json('refresh_token');

        // First refresh - should work
        $firstRefresh = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'refresh_token' => $refreshToken,
        ]);
        $firstRefresh->assertStatus(200);

        // Second refresh with same token - should fail
        $secondRefresh = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'refresh_token' => $refreshToken,
        ]);
        $secondRefresh->assertStatus(400);
    }

    /**
     * Test token revocation
     */
    public function test_token_revocation(): void
    {
        $client = OAuthClient::where('revoked', false)->first();
        $employee = EEmployee::where('active', true)->first();

        if (!$client || !$employee) {
            $this->markTestSkipped('Missing required test data');
        }

        // Get access token
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $employee->login,
            'password' => 'admin123',
        ]);
        $employeeToken = $loginResponse->json('data.access_token');

        $grantResponse = $this->postJson('/api/v1/oauth/authorize', [
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
        ], [
            'Authorization' => 'Bearer ' . $employeeToken,
        ]);

        $authCode = $grantResponse->json('data.code');

        $tokenResponse = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'code' => $authCode,
            'redirect_uri' => $client->redirect,
        ]);

        $accessToken = $tokenResponse->json('access_token');

        // Revoke token
        $revokeResponse = $this->postJson('/api/v1/oauth/revoke', [
            'token' => $accessToken,
            'token_type_hint' => 'access_token',
        ]);

        $revokeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token revoked',
            ]);

        // Try to use revoked token
        $userinfoResponse = $this->getJson('/api/v1/oauth/userinfo?access_token=' . $accessToken);
        $userinfoResponse->assertStatus(401);
    }

    /**
     * Test userinfo with invalid token
     */
    public function test_userinfo_with_invalid_token(): void
    {
        $response = $this->getJson('/api/v1/oauth/userinfo?access_token=invalid-token-123');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'invalid_token',
            ]);
    }

    /**
     * Test userinfo without token
     */
    public function test_userinfo_without_token(): void
    {
        $response = $this->getJson('/api/v1/oauth/userinfo');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_request',
            ]);
    }

    /**
     * Test token endpoint requires grant_type
     */
    public function test_token_endpoint_requires_grant_type(): void
    {
        $response = $this->postJson('/api/v1/oauth/token', [
            'client_id' => 1,
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['error', 'error_description']);
    }

    /**
     * Test token endpoint requires client_id
     */
    public function test_token_endpoint_requires_client_id(): void
    {
        $response = $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'authorization_code',
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['error', 'error_description']);
    }
}
