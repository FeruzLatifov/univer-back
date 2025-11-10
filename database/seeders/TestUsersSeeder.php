<?php

namespace Database\Seeders;

use App\Models\EAdmin;
use App\Models\EEmployee;
use App\Models\EStudent;
use App\Models\OAuthClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Test Users Seeder
 *
 * Creates standard test users for authentication testing
 * Run before tests: php artisan db:seed --class=TestUsersSeeder
 */
class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->createTestEmployees();
            $this->createTestAdmins();
            $this->createTestStudents();
            $this->createOAuthClients();
        });

        $this->command->info('✅ Test users created successfully!');
        $this->command->newLine();
        $this->command->info('Test Admin Credentials:');
        $this->command->info('  Login: test_admin');
        $this->command->info('  Password: admin123');
        $this->command->newLine();
        $this->command->info('Test Student Credentials:');
        $this->command->info('  Student ID: TEST001');
        $this->command->info('  Password: student123');
    }

    /**
     * Create test employees
     */
    private function createTestEmployees(): void
    {
        $employees = [
            [
                'first_name' => 'Test',
                'second_name' => 'Admin',
                'third_name' => 'User',
                'birth_date' => '1990-01-01',
                'email' => 'test_admin@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Test',
                'second_name' => 'Employee',
                'third_name' => 'Two',
                'birth_date' => '1991-02-02',
                'email' => 'test_employee2@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Inactive',
                'second_name' => 'Employee',
                'third_name' => 'Test',
                'birth_date' => '1992-03-03',
                'email' => 'inactive@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($employees as $employee) {
            EEmployee::updateOrCreate(
                ['email' => $employee['email']],
                $employee
            );
        }

        $this->command->info('✅ Created test employees');
    }

    /**
     * Create test admin users
     */
    private function createTestAdmins(): void
    {
        // Get employees
        $employee1 = EEmployee::where('email', 'test_admin@example.com')->first();
        $employee2 = EEmployee::where('email', 'test_employee2@example.com')->first();
        $employee3 = EEmployee::where('email', 'inactive@example.com')->first();

        $admins = [
            [
                'login' => 'test_admin',
                'email' => 'test_admin@example.com',
                'password' => Hash::make('admin123'),
                'full_name' => 'Test Admin User',
                '_employee' => $employee1?->id,
                'status' => 'enable', // Active
                'language' => 'uz',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'login' => 'test_admin2',
                'email' => 'test_employee2@example.com',
                'password' => Hash::make('admin123'),
                'full_name' => 'Test Employee Two',
                '_employee' => $employee2?->id,
                'status' => 'enable', // Active
                'language' => 'uz',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'login' => 'inactive_admin',
                'email' => 'inactive@example.com',
                'password' => Hash::make('admin123'),
                'full_name' => 'Inactive Employee Test',
                '_employee' => $employee3?->id,
                'status' => 'disable', // Inactive
                'language' => 'uz',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($admins as $admin) {
            EAdmin::updateOrCreate(
                ['login' => $admin['login']],
                $admin
            );
        }

        $this->command->info('✅ Created test admins (2 active, 1 inactive)');
    }

    /**
     * Create test students
     */
    private function createTestStudents(): void
    {
        $students = [
            [
                'student_id_number' => 'TEST001',
                'first_name' => 'Test',
                'second_name' => 'Student',
                'third_name' => 'One',
                'birth_date' => '2000-01-01',
                'email' => 'test_student@example.com',
                'password' => Hash::make('student123'),
                'phone' => '+998901234567',
                '_gender' => '11', // Default gender code
                '_nationality' => '182', // Uzbekistan
                'home_address' => 'Test Address',
                'current_address' => 'Test Address',
                'year_of_enter' => 2020,
                'position' => 0,
                'active' => true,
                'account_active' => true,
                '_sync' => false,
                'pin_verified' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id_number' => 'TEST002',
                'first_name' => 'Test',
                'second_name' => 'Student',
                'third_name' => 'Two',
                'birth_date' => '2000-02-02',
                'email' => 'test_student2@example.com',
                'password' => Hash::make('student123'),
                'phone' => '+998901234568',
                '_gender' => '11',
                '_nationality' => '182',
                'home_address' => 'Test Address 2',
                'current_address' => 'Test Address 2',
                'year_of_enter' => 2020,
                'position' => 0,
                'active' => true,
                'account_active' => true,
                '_sync' => false,
                'pin_verified' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id_number' => 'TEST003',
                'first_name' => 'Inactive',
                'second_name' => 'Student',
                'third_name' => 'Three',
                'birth_date' => '2000-03-03',
                'email' => 'inactive_student@example.com',
                'password' => Hash::make('student123'),
                'phone' => '+998901234569',
                '_gender' => '11',
                '_nationality' => '182',
                'home_address' => 'Test Address 3',
                'current_address' => 'Test Address 3',
                'year_of_enter' => 2020,
                'position' => 0,
                'active' => false,
                'account_active' => false,
                '_sync' => false,
                'pin_verified' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($students as $student) {
            EStudent::updateOrCreate(
                ['student_id_number' => $student['student_id_number']],
                $student
            );
        }

        $this->command->info('✅ Created test students (2 active, 1 inactive)');
    }

    /**
     * Create OAuth test clients
     */
    private function createOAuthClients(): void
    {
        $clients = [
            [
                'name' => 'Test OAuth Client',
                'secret' => Hash::make('test_secret'),
                'redirect' => 'http://localhost:5173/callback',
                'token_type' => 1, // bearer
                'grant_type' => 1, // authorization_code
                'revoked' => false, // Active
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Revoked OAuth Client',
                'secret' => Hash::make('test_secret'),
                'redirect' => 'http://localhost:5173/callback',
                'token_type' => 1, // bearer
                'grant_type' => 1, // authorization_code
                'revoked' => true, // Revoked
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($clients as $client) {
            OAuthClient::updateOrCreate(
                ['name' => $client['name']],
                $client
            );
        }

        $this->command->info('✅ Created OAuth clients (1 active, 1 revoked)');
    }
}
