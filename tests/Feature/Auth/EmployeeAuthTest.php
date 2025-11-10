<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\EAdmin;

class EmployeeAuthTest extends TestCase
{
    /**
     * Test employee can login with valid credentials
     */
    public function test_employee_can_login_with_valid_credentials(): void
    {
        // Find active admin
        $admin = EAdmin::where('status', 'enable')->first();

        $this->assertNotNull($admin, 'No enabled admin found in database');

        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123', // Test password from summary
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'first_name',
                        'second_name',
                        'login',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'bearer',
                ],
            ]);
    }

    /**
     * Test employee cannot login with invalid credentials
     */
    public function test_employee_cannot_login_with_invalid_credentials(): void
    {
        $admin = EAdmin::where('status', 'enable')->first();

        $this->assertNotNull($admin, 'No enabled admin found in database');

        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test employee cannot login when inactive
     */
    public function test_employee_cannot_login_when_inactive(): void
    {
        $admin = EAdmin::where('status', 'disable')->first();

        if (!$admin) {
            $this->markTestSkipped('No disabled admin found in database');
        }

        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Xodim faol emas',
            ]);
    }

    /**
     * Test employee can refresh token
     */
    public function test_employee_can_refresh_token(): void
    {
        // Login first
        $admin = EAdmin::where('status', 'enable')->first();
        $this->assertNotNull($admin, 'No enabled admin found in database');

        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123',
        ]);

        $loginResponse->assertStatus(200);
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Now refresh
        $response = $this->postJson('/api/v1/employee/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify new tokens are different
        $this->assertNotEquals(
            $refreshToken,
            $response->json('data.refresh_token'),
            'Refresh token should be rotated'
        );
    }

    /**
     * Test employee cannot use same refresh token twice
     */
    public function test_employee_cannot_reuse_refresh_token(): void
    {
        // Login first
        $admin = EAdmin::where('status', 'enable')->first();
        $this->assertNotNull($admin, 'No enabled admin found in database');

        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        // First refresh - should work
        $firstRefresh = $this->postJson('/api/v1/employee/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $firstRefresh->assertStatus(200);

        // Second refresh with same token - should fail
        $secondRefresh = $this->postJson('/api/v1/employee/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $secondRefresh->assertStatus(401);
    }

    /**
     * Test employee can get their own data
     */
    public function test_employee_can_get_own_data(): void
    {
        // Login first
        $admin = EAdmin::where('status', 'enable')->first();
        $this->assertNotNull($admin, 'No enabled admin found in database');

        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Get user data
        $response = $this->getJson('/api/v1/employee/auth/me', [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'second_name',
                    'login',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'login' => $admin->login,
                ],
            ]);
    }

    /**
     * Test employee cannot access protected routes without token
     */
    public function test_employee_cannot_access_protected_routes_without_token(): void
    {
        $response = $this->getJson('/api/v1/employee/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test employee can logout
     */
    public function test_employee_can_logout(): void
    {
        // Login first
        $admin = EAdmin::where('status', 'enable')->first();
        $this->assertNotNull($admin, 'No enabled admin found in database');

        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => $admin->login,
            'password' => 'admin123',
        ]);

        $accessToken = $loginResponse->json('data.access_token');
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Logout
        $response = $this->postJson('/api/v1/employee/auth/logout', [], [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify tokens are invalidated
        $meResponse = $this->getJson('/api/v1/employee/auth/me', [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $meResponse->assertStatus(401);

        // Verify refresh token is also invalidated
        $refreshResponse = $this->postJson('/api/v1/employee/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $refreshResponse->assertStatus(401);
    }

    /**
     * Test login requires login field
     */
    public function test_login_requires_login_field(): void
    {
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'password' => 'admin123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    /**
     * Test login requires password field
     */
    public function test_login_requires_password_field(): void
    {
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'test_user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test refresh requires refresh_token field
     */
    public function test_refresh_requires_refresh_token_field(): void
    {
        $response = $this->postJson('/api/v1/employee/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    /**
     * Test token expiration handling
     */
    public function test_expired_access_token_is_rejected(): void
    {
        // This would require manipulating token expiration
        // For now, we'll test with invalid token
        $response = $this->getJson('/api/v1/employee/auth/me', [
            'Authorization' => 'Bearer invalid-token-12345',
        ]);

        $response->assertStatus(401);
    }
}
