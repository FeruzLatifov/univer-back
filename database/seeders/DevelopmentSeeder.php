<?php

namespace Database\Seeders;

use App\Models\EAdmin;
use App\Models\EStudent;
use App\Models\EEmployee;
use App\Models\EGroup;
use App\Models\ESpecialty;
use App\Models\EDepartment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development Seeder
 *
 * Best Practice: Seed realistic test data for development
 * Usage: php artisan db:seed --class=DevelopmentSeeder
 */
class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding development data...');

        // 1. Create Admin Users
        $this->command->info('Creating admin users...');

        $superAdmin = EAdmin::create([
            'login' => 'admin',
            'password' => Hash::make('admin123'),
            'full_name' => 'System Administrator',
            'email' => 'admin@univer.uz',
            'telephone' => '+998901234567',
            '_role' => 'admin',
            'status' => '10',
            'active' => true,
        ]);
        $this->command->info("✅ Super Admin created: login=admin, password=admin123");

        $rector = EAdmin::create([
            'login' => 'rector',
            'password' => Hash::make('rector123'),
            'full_name' => 'Rektor Karimov',
            'email' => 'rector@univer.uz',
            'telephone' => '+998901234568',
            '_role' => 'rector',
            'status' => '10',
            'active' => true,
        ]);
        $this->command->info("✅ Rector created: login=rector, password=rector123");

        $dean = EAdmin::create([
            'login' => 'dean',
            'password' => Hash::make('dean123'),
            'full_name' => 'Dekan Aliyev',
            'email' => 'dean@univer.uz',
            'telephone' => '+998901234569',
            '_role' => 'dean',
            'status' => '10',
            'active' => true,
        ]);
        $this->command->info("✅ Dean created: login=dean, password=dean123");

        // Create additional admins
        EAdmin::factory()->count(5)->create();
        $this->command->info("✅ 5 additional admin users created");

        // 2. Create Test Students
        $this->command->info('Creating test students...');

        $testStudent = EStudent::create([
            'first_name' => 'Ahmad',
            'second_name' => 'Karimov',
            'third_name' => 'Aliyevich',
            'student_id_number' => 'ST001',
            'birth_date' => '2003-01-15',
            '_gender' => '11',
            '_country' => '182',
            'passport_number' => 'AB1234567',
            'passport_pin' => '12345678901234',
            'phone_number' => '+998901111111',
            'email' => 'ahmad@student.uz',
            'password' => Hash::make('student123'),
            'active' => true,
        ]);
        $this->command->info("✅ Test Student created: student_id=ST001, password=student123");

        // Create more students with factory
        EStudent::factory()->count(50)->create();
        $this->command->info("✅ 50 additional students created");

        // 3. Create Employees
        $this->command->info('Creating employees...');
        EEmployee::factory()->count(20)->create();
        $this->command->info("✅ 20 employees created");

        // Summary
        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('🎉 Development seeding completed!');
        $this->command->info('========================================');
        $this->command->newLine();
        $this->command->table(
            ['Type', 'Login', 'Password'],
            [
                ['Super Admin', 'admin', 'admin123'],
                ['Rector', 'rector', 'rector123'],
                ['Dean', 'dean', 'dean123'],
                ['Test Student', 'ST001', 'student123'],
            ]
        );
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('   - Admins: ' . EAdmin::count());
        $this->command->info('   - Students: ' . EStudent::count());
        $this->command->info('   - Employees: ' . EEmployee::count());
        $this->command->newLine();
    }
}
