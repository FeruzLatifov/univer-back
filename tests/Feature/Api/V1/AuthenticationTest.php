<?php

namespace Tests\Feature\Api\V1;

use App\Models\EAdmin;
use App\Models\EStudent;
use Tests\SeedsTestData;
use Tests\TestCase;

/**
 * Authentication Tests
 *
 * Best Practice: Test all authentication flows
 * Coverage: Login, logout, refresh, me endpoints for both staff and students
 *
 * Uses seeded test data from TestUsersSeeder:
 * - Admin: login=test_admin, password=admin123
 * - Student: student_id=TEST001, password=student123
 * - Inactive Admin: login=inactive_admin, password=admin123
 */
class AuthenticationTest extends TestCase
{
    use SeedsTestData;

    /**
     * Test employee login with valid credentials
     */
    public function test_employee_can_login_with_valid_credentials(): void
    {
        // Use seeded test admin
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'test_admin',
            'password' => 'admin123',
        ]);

        // Assert success
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'type',
                        'login',
                        'full_name',
                        'role',
                        'active',
                    ],
                    'access_token',
                    'token_type',
                    'expires_in',
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
     * Test employee login with invalid credentials
     */
    public function test_employee_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'test_admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test employee login with inactive account
     */
    public function test_inactive_employee_cannot_login(): void
    {
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'inactive_admin',
            'password' => 'admin123',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test student login with valid credentials
     */
    public function test_student_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => 'TEST001',
            'password' => 'student123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'type',
                        'student_id_number',
                        'full_name',
                        'active',
                    ],
                    'access_token',
                    'token_type',
                    'expires_in',
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
     * Test student login with invalid credentials
     */
    public function test_student_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => 'TEST001',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test employee can get their profile
     */
    public function test_authenticated_employee_can_get_profile(): void
    {
        $admin = EAdmin::where('login', 'test_admin')->first();
        $token = auth('employee-api')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/employee/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'login',
                    'full_name',
                ],
            ]);
    }

    /**
     * Test student can get their profile
     */
    public function test_authenticated_student_can_get_profile(): void
    {
        $student = EStudent::where('student_id_number', 'TEST001')->first();
        $token = auth('student-api')->login($student);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/student/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'student_id_number',
                ],
            ]);
    }

    /**
     * Test unauthenticated request to protected endpoint
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/employee/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test employee can logout
     */
    public function test_authenticated_employee_can_logout(): void
    {
        $admin = EAdmin::where('login', 'test_admin')->first();
        $token = auth('employee-api')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/employee/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test employee can refresh token
     */
    public function test_authenticated_employee_can_refresh_token(): void
    {
        // First login to get refresh_token
        $loginResponse = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'test_admin',
            'password' => 'admin123',
        ]);

        $loginResponse->assertStatus(200);
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Now use refresh_token to get new access_token
        $response = $this->postJson('/api/v1/employee/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    /**
     * Test validation errors on employee login
     */
    public function test_employee_login_validation_errors(): void
    {
        // Test missing login field
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => '', // Empty login
            'password' => 'validpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        // Test missing password field
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'test_user',
            'password' => '', // Empty password
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test validation errors on student login
     */
    public function test_student_login_validation_errors(): void
    {
        // Test missing student_id field
        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => '', // Empty student_id
            'password' => 'validpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);

        // Test missing password field
        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => 'TEST999',
            'password' => '', // Empty password
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
