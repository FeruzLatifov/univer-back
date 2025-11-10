<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Student;

class StudentAuthTest extends TestCase
{
    /**
     * Test student can login with valid credentials
     */
    public function test_student_can_login_with_valid_credentials(): void
    {
        // Find active student
        $student = Student::where('_student_status', '!=', 3) // Not expelled
            ->whereNotNull('student_id_number')
            ->first();

        if (!$student) {
            $this->markTestSkipped('No active student found in database');
        }

        $response = $this->postJson('/api/v1/student/auth/login', [
            'login' => $student->student_id_number,
            'password' => 'admin123',
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
                        'third_name',
                        'student_id_number',
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
     * Test student cannot login with invalid credentials
     */
    public function test_student_cannot_login_with_invalid_credentials(): void
    {
        $student = Student::whereNotNull('student_id_number')->first();

        if (!$student) {
            $this->markTestSkipped('No student found in database');
        }

        $response = $this->postJson('/api/v1/student/auth/login', [
            'login' => $student->student_id_number,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test student can refresh token
     */
    public function test_student_can_refresh_token(): void
    {
        // Login first
        $student = Student::where('_student_status', '!=', 3)
            ->whereNotNull('student_id_number')
            ->first();

        if (!$student) {
            $this->markTestSkipped('No active student found in database');
        }

        $loginResponse = $this->postJson('/api/v1/student/auth/login', [
            'login' => $student->student_id_number,
            'password' => 'admin123',
        ]);

        $loginResponse->assertStatus(200);
        $refreshToken = $loginResponse->json('data.refresh_token');

        // Now refresh
        $response = $this->postJson('/api/v1/student/auth/refresh', [
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
     * Test student cannot reuse refresh token
     */
    public function test_student_cannot_reuse_refresh_token(): void
    {
        $student = Student::where('_student_status', '!=', 3)
            ->whereNotNull('student_id_number')
            ->first();

        if (!$student) {
            $this->markTestSkipped('No active student found in database');
        }

        $loginResponse = $this->postJson('/api/v1/student/auth/login', [
            'login' => $student->student_id_number,
            'password' => 'admin123',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        // First refresh - should work
        $firstRefresh = $this->postJson('/api/v1/student/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $firstRefresh->assertStatus(200);

        // Second refresh with same token - should fail
        $secondRefresh = $this->postJson('/api/v1/student/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $secondRefresh->assertStatus(401);
    }

    /**
     * Test login requires login field
     */
    public function test_login_requires_login_field(): void
    {
        $response = $this->postJson('/api/v1/student/auth/login', [
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
        $response = $this->postJson('/api/v1/student/auth/login', [
            'login' => '401231100286',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
