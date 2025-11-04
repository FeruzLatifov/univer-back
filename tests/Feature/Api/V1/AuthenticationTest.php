<?php

namespace Tests\Feature\Api\V1;

use App\Models\EAdmin;
use App\Models\EStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication Tests
 *
 * Best Practice: Test all authentication flows
 * Coverage: Login, logout, refresh, me endpoints for both staff and students
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test employee login with valid credentials
     */
    public function test_employee_can_login_with_valid_credentials(): void
    {
        // Create a test admin
        $admin = EAdmin::factory()->create([
            'login' => 'testadmin',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        // Attempt login
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'testadmin',
            'password' => 'password123',
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
        $admin = EAdmin::factory()->create([
            'login' => 'testadmin',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'testadmin',
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
        $admin = EAdmin::factory()->create([
            'login' => 'testadmin',
            'password' => Hash::make('password123'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => 'testadmin',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test student login with valid credentials
     */
    public function test_student_can_login_with_valid_credentials(): void
    {
        $student = EStudent::factory()->create([
            'student_id_number' => 'ST001',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => 'ST001',
            'password' => 'password123',
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
        $student = EStudent::factory()->create([
            'student_id_number' => 'ST001',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => 'ST001',
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
        $admin = EAdmin::factory()->create([
            'active' => true,
        ]);

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
        $student = EStudent::factory()->create([
            'active' => true,
        ]);

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
        $admin = EAdmin::factory()->create(['active' => true]);
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
        $admin = EAdmin::factory()->create(['active' => true]);
        $token = auth('employee-api')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/employee/auth/refresh');

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
        $response = $this->postJson('/api/v1/employee/auth/login', [
            'login' => '', // Empty login
            'password' => '123', // Too short
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login', 'password']);
    }

    /**
     * Test validation errors on student login
     */
    public function test_student_login_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_id' => '', // Empty student_id
            'password' => '123', // Too short
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'password']);
    }
}
