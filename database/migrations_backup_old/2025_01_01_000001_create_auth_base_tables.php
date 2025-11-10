<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Auth Base Tables Migration
 *
 * Creates core authentication tables if they don't exist (Yii2 compatibility)
 * - e_admin (staff/employee users)
 * - e_student (student users)
 * - e_employee (employee details)
 * - h_language (supported languages)
 *
 * SAFE: Checks if tables exist before creating (supports existing Yii2 database)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. h_language - Language configuration table
        if (!Schema::hasTable('h_language')) {
            Schema::create('h_language', function (Blueprint $table) {
                $table->string('code', 32)->primary();
                $table->string('name', 256)->nullable();
                $table->string('type', 32)->default('locale');
                $table->boolean('active')->default(true);
                $table->integer('position')->default(0);
                $table->jsonb('_options')->nullable();
                $table->timestamps();

                $table->index('active');
                $table->index('position');
            });

            // Insert default languages
            DB::table('h_language')->insert([
                ['code' => 'uz', 'name' => 'O\'zbekcha', 'type' => 'locale', 'active' => true, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'oz', 'name' => 'Ўзбекча', 'type' => 'locale', 'active' => true, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'ru', 'name' => 'Русский', 'type' => 'locale', 'active' => true, 'position' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'en', 'name' => 'English', 'type' => 'locale', 'active' => true, 'position' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. e_employee - Employee details table
        if (!Schema::hasTable('e_employee')) {
            Schema::create('e_employee', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 100);
                $table->string('second_name', 100);
                $table->string('third_name', 100)->nullable();
                $table->date('birth_date');
                $table->string('employee_id_number', 14)->unique()->nullable();
                $table->string('passport_number', 14)->nullable();
                $table->string('passport_pin', 20)->nullable();
                $table->string('_gender', 64);
                $table->string('_country', 64)->nullable();
                $table->date('hire_date')->nullable();
                $table->jsonb('image')->nullable();
                $table->boolean('active')->default(true);
                $table->jsonb('_translations')->nullable();
                $table->timestamps();

                $table->index('employee_id_number');
                $table->index('passport_number');
                $table->index('active');
            });
        }

        // 3. e_admin_role - Admin roles table (Yii2 hierarchical roles)
        if (!Schema::hasTable('e_admin_role')) {
            Schema::create('e_admin_role', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name', 32)->unique();
                $table->string('status', 16);
                $table->unsignedBigInteger('parent')->nullable();
                $table->jsonb('_options')->nullable();
                $table->jsonb('_translations')->nullable();
                $table->integer('position')->default(0);
                $table->timestamps();

                $table->foreign('parent')->references('id')->on('e_admin_role')->onUpdate('cascade')->onDelete('restrict');
                $table->index('status');
                $table->index('position');
            });
        }

        // 4. e_admin_resource - Admin resources/permissions table (Yii2 path-based permissions)
        if (!Schema::hasTable('e_admin_resource')) {
            Schema::create('e_admin_resource', function (Blueprint $table) {
                $table->id();
                $table->string('path', 128)->unique();
                $table->string('name', 256)->unique();
                $table->string('group', 64);
                $table->text('comment')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('login')->default(false);
                $table->boolean('skip')->default(false);
                $table->jsonb('_options')->nullable();
                $table->timestamps();

                $table->index('active');
                $table->index('group');
            });
        }

        // 5. e_admin - Admin/Staff users table
        if (!Schema::hasTable('e_admin')) {
            Schema::create('e_admin', function (Blueprint $table) {
                $table->id();
                $table->string('login', 255)->unique();
                $table->unsignedBigInteger('_role')->nullable();
                $table->string('password', 255);
                $table->string('email', 64)->unique()->nullable();
                $table->string('telephone', 32)->nullable();
                $table->string('full_name', 128)->nullable();
                $table->string('auth_key', 32)->nullable();
                $table->string('access_token', 32)->unique()->nullable();
                $table->string('password_reset_token', 32)->unique()->nullable();
                $table->timestamp('password_reset_date')->nullable();
                $table->jsonb('image')->nullable();
                $table->string('language', 32)->default('uz-UZ');
                $table->string('status', 32)->default('enable');
                $table->unsignedBigInteger('_employee')->nullable();
                $table->boolean('password_valid')->default(true);
                $table->timestamp('password_date')->nullable();
                $table->uuid('user_uuid')->unique()->nullable()->default(DB::raw('gen_random_uuid()'));
                $table->timestamps();

                $table->foreign('_role')->references('id')->on('e_admin_role')->onUpdate('cascade')->onDelete('restrict');
                $table->foreign('_employee')->references('id')->on('e_employee')->onUpdate('cascade')->onDelete('cascade');

                $table->index('status');
                $table->index('language');
                $table->index(['access_token', 'status']);
            });
        }

        // 6. e_admin_role_resource - Role-Permission pivot table
        if (!Schema::hasTable('e_admin_role_resource')) {
            Schema::create('e_admin_role_resource', function (Blueprint $table) {
                $table->unsignedBigInteger('_role');
                $table->unsignedBigInteger('_resource');

                $table->foreign('_role')->references('id')->on('e_admin_role')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('_resource')->references('id')->on('e_admin_resource')->onUpdate('cascade')->onDelete('cascade');

                $table->primary(['_role', '_resource']);
            });
        }

        // 7. e_admin_roles - Admin-Role pivot table (many-to-many)
        if (!Schema::hasTable('e_admin_roles')) {
            Schema::create('e_admin_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('_admin');
                $table->unsignedBigInteger('_role');

                $table->foreign('_admin')->references('id')->on('e_admin')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('_role')->references('id')->on('e_admin_role')->onUpdate('cascade')->onDelete('cascade');

                $table->primary(['_admin', '_role']);
            });
        }

        // 8. e_student - Student users table
        if (!Schema::hasTable('e_student')) {
            Schema::create('e_student', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 100);
                $table->string('second_name', 100);
                $table->string('third_name', 100)->nullable();
                $table->date('birth_date');
                $table->string('student_id_number', 14)->nullable();
                $table->string('passport_number', 14)->nullable();
                $table->string('passport_pin', 20)->nullable();
                $table->string('_gender', 64);
                $table->string('_nationality', 64);
                $table->string('_citizenship', 64)->nullable();
                $table->string('_country', 64)->nullable();
                $table->string('_province', 64)->nullable();
                $table->string('_district', 64)->nullable();
                $table->string('home_address', 255);
                $table->string('current_address', 255);
                $table->integer('year_of_enter');
                $table->jsonb('image')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('active')->default(true);
                $table->jsonb('_translations')->nullable();
                $table->string('password', 256)->nullable();
                $table->string('auth_key', 32)->nullable();
                $table->string('access_token', 32)->nullable();
                $table->string('password_reset_token', 32)->nullable();
                $table->timestamp('password_reset_date')->nullable();
                $table->uuid('user_uuid')->unique()->nullable()->default(DB::raw('gen_random_uuid()'));
                $table->string('email', 64)->nullable();
                $table->string('phone', 20)->nullable();
                $table->boolean('password_valid')->default(true);
                $table->timestamp('password_date')->nullable();
                $table->boolean('account_active')->default(true);
                $table->timestamps();

                $table->index('student_id_number');
                $table->index(['passport_number', 'year_of_enter']);
                $table->index(['passport_pin', 'year_of_enter']);
                $table->index('active');
                $table->index('account_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse order to respect foreign keys
        Schema::dropIfExists('e_student');
        Schema::dropIfExists('e_admin_roles');
        Schema::dropIfExists('e_admin_role_resource');
        Schema::dropIfExists('e_admin');
        Schema::dropIfExists('e_admin_resource');
        Schema::dropIfExists('e_admin_role');
        Schema::dropIfExists('e_employee');
        Schema::dropIfExists('h_language');
    }
};
