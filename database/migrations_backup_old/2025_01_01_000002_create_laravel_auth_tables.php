<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel Auth Tables Migration
 *
 * Creates Laravel-specific authentication tables:
 * - password_reset_tokens (password reset functionality)
 * - auth_refresh_tokens (JWT refresh token management)
 * - system_login (login attempts tracking & rate limiting)
 *
 * SAFE: Checks if tables exist before creating
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. password_reset_tokens - Password reset tokens
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('token', 64)->unique();
                $table->enum('user_type', ['student', 'employee'])->default('student');
                $table->timestamp('expires_at');
                $table->timestamp('created_at');

                // Index for faster lookups
                $table->index(['email', 'user_type', 'expires_at'], 'idx_password_reset_lookup');
            });
        }

        // 2. auth_refresh_tokens - JWT refresh tokens
        if (!Schema::hasTable('auth_refresh_tokens')) {
            Schema::create('auth_refresh_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('user_type', 20); // 'student' or 'employee'
                $table->string('token_hash', 128)->unique();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index(['user_id', 'user_type'], 'idx_user_lookup');
                $table->index('expires_at');
            });
        }

        // 3. system_login - Login attempts tracking (rate limiting & security)
        if (!Schema::hasTable('system_login')) {
            Schema::create('system_login', function (Blueprint $table) {
                $table->id();
                $table->string('login', 255)->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->enum('type', ['login', 'logout', 'refresh', 'failed'])->default('login');
                $table->boolean('success')->default(true);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_type', 20)->nullable(); // 'student' or 'employee'
                $table->text('error_message')->nullable();
                $table->timestamp('attempted_at');
                $table->timestamps();

                $table->index(['login', 'attempted_at'], 'idx_login_attempts');
                $table->index(['ip_address', 'attempted_at'], 'idx_ip_attempts');
                $table->index(['success', 'type']);
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_login');
        Schema::dropIfExists('auth_refresh_tokens');
        Schema::dropIfExists('password_reset_tokens');
    }
};
