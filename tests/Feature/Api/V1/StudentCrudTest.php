<?php

namespace Tests\Feature\Api\V1;

use App\Models\EAdmin;
use App\Models\EStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Student CRUD Tests
 *
 * Test all CRUD operations for students
 */
class StudentCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected EAdmin $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin with proper role
        $this->admin = EAdmin::factory()->create([
            'active' => true,
            '_role' => 'admin',
        ]);

        $this->token = auth('staff-api')->login($this->admin);
    }

    /**
     * Test admin can view students list
     */
    public function test_admin_can_view_students_list(): void
    {
        EStudent::factory()->count(5)->create(['active' => true]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/admin/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'student_id_number',
                        'full_name',
                        'active',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test admin can view single student
     */
    public function test_admin_can_view_single_student(): void
    {
        $student = EStudent::factory()->create(['active' => true]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson("/api/v1/admin/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                ],
            ]);
    }

    /**
     * Test admin can create student
     */
    public function test_admin_can_create_student(): void
    {
        $studentData = [
            'first_name' => 'Ahmad',
            'second_name' => 'Karimov',
            'third_name' => 'Aliyevich',
            'birth_date' => '2000-01-01',
            'student_id_number' => 'ST' . rand(10000, 99999),
            '_gender' => '11', // Assuming this code exists
            '_country' => '182', // Uzbekistan
            'phone_number' => '+998901234567',
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v1/admin/students', $studentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Student muvaffaqiyatli yaratildi',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'student_id_number',
                    'full_name',
                ],
                'message',
            ]);

        // Assert student exists in database
        $this->assertDatabaseHas('e_student', [
            'student_id_number' => $studentData['student_id_number'],
            'first_name' => 'Ahmad',
            'second_name' => 'Karimov',
        ]);
    }

    /**
     * Test admin can update student
     */
    public function test_admin_can_update_student(): void
    {
        $student = EStudent::factory()->create(['active' => true]);

        $updateData = [
            'first_name' => 'Updated',
            'second_name' => 'Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson("/api/v1/admin/students/{$student->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student muvaffaqiyatli yangilandi',
            ]);

        // Assert changes in database
        $this->assertDatabaseHas('e_student', [
            'id' => $student->id,
            'first_name' => 'Updated',
            'second_name' => 'Name',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test admin can delete (deactivate) student
     */
    public function test_admin_can_delete_student(): void
    {
        $student = EStudent::factory()->create(['active' => true]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson("/api/v1/admin/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student o\'chirildi (deactivated)',
            ]);

        // Assert student is deactivated
        $this->assertDatabaseHas('e_student', [
            'id' => $student->id,
            'active' => false,
        ]);
    }

    /**
     * Test validation errors on student creation
     */
    public function test_create_student_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v1/admin/students', [
            'first_name' => '', // Required
            'student_id_number' => '', // Required
            'email' => 'invalid-email', // Invalid format
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'student_id_number', 'email']);
    }

    /**
     * Test unauthenticated user cannot access students
     */
    public function test_unauthenticated_user_cannot_access_students(): void
    {
        $response = $this->getJson('/api/v1/admin/students');

        $response->assertStatus(401);
    }

    /**
     * Test filtering students by active status
     */
    public function test_can_filter_students_by_active_status(): void
    {
        EStudent::factory()->count(3)->create(['active' => true]);
        EStudent::factory()->count(2)->create(['active' => false]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/admin/students?filter[active]=1');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test pagination
     */
    public function test_students_list_is_paginated(): void
    {
        EStudent::factory()->count(25)->create(['active' => true]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/admin/students?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(10, 'data');
    }

    /**
     * Test student not found returns 404
     */
    public function test_viewing_nonexistent_student_returns_404(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/admin/students/99999');

        $response->assertStatus(404);
    }
}
