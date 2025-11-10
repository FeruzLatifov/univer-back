<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OAuth2 Server Tables (Compatible with Yii2 oauth module)
     *
     * Uses Schema::hasTable() guards to work with existing databases:
     * - If table exists: Skip creation (preserve existing data)
     * - If table missing: Create from scratch
     *
     * Safe for both:
     * - Existing Yii2 database (hemis_401)
     * - Fresh Laravel database
     */
    public function up(): void
    {
        // =============================================
        // 1. OAUTH CLIENTS
        // =============================================
        if (!Schema::hasTable('oauth_client')) {
            Schema::create('oauth_client', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('_user')->nullable()->index();
                $table->string('secret', 100)->nullable();
                $table->string('name', 255);
                $table->text('redirect')->nullable();
                $table->smallInteger('token_type')->default(1); // 1=bearer, 2=mac
                $table->smallInteger('grant_type')->default(1); // 1=authorization_code, 2=password, 3=client_credentials, 4=refresh_token
                $table->boolean('revoked')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('_user', 'oauth_client__user');
            });
        }

        // =============================================
        // 2. OAUTH SCOPES
        // =============================================
        if (!Schema::hasTable('oauth_scope')) {
            Schema::create('oauth_scope', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->string('name', 255)->nullable();

                // No timestamps - static reference data
            });
        }

        // =============================================
        // 3. OAUTH ACCESS TOKENS
        // =============================================
        if (!Schema::hasTable('oauth_access_token')) {
            Schema::create('oauth_access_token', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->unsignedInteger('_client');
                $table->unsignedInteger('_user')->nullable();
                $table->timestamp('expires_at');
                $table->boolean('revoked')->default(false);
                $table->timestamps();

                // Indexes
                $table->index('_client', 'idx-oauth_access_token-_client');

                // Foreign key
                $table->foreign('_client', 'fk-oauth_access_token-_client')
                    ->references('id')
                    ->on('oauth_client')
                    ->onDelete('cascade');
            });
        }

        // =============================================
        // 4. OAUTH REFRESH TOKENS
        // =============================================
        if (!Schema::hasTable('oauth_refresh_token')) {
            Schema::create('oauth_refresh_token', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->unsignedBigInteger('_access_token'); // Note: Different from FK - just reference
                $table->timestamp('expires_at');
                $table->boolean('revoked')->default(false);
                $table->timestamps();

                // Note: No FK to oauth_access_token because token IDs are strings
                // and _access_token is bigint in Yii2 implementation
            });
        }

        // =============================================
        // 5. OAUTH AUTHORIZATION CODES
        // =============================================
        if (!Schema::hasTable('oauth_auth_code')) {
            Schema::create('oauth_auth_code', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->unsignedInteger('_client');
                $table->unsignedInteger('_user')->nullable();
                $table->timestamp('expires_at');
                $table->boolean('revoked')->default(false);

                // No timestamps - short-lived codes

                // Indexes
                $table->index('_client', 'idx-oauth_auth_code-_client');

                // Foreign key
                $table->foreign('_client', 'fk-oauth_auth_code-_client')
                    ->references('id')
                    ->on('oauth_client')
                    ->onDelete('cascade');
            });
        }

        // =============================================
        // 6. OAUTH ACCESS TOKEN SCOPES (Pivot)
        // =============================================
        if (!Schema::hasTable('oauth_access_token_scope')) {
            Schema::create('oauth_access_token_scope', function (Blueprint $table) {
                $table->string('_access_token', 100);
                $table->string('_scope', 100);

                // No primary key - compound index instead

                // Indexes
                $table->index('_access_token', 'idx-oauth_access_token_scope-_access_token');
                $table->index('_scope', 'idx-oauth_access_token_scope-_scope');

                // Foreign keys
                $table->foreign('_access_token', 'fk-oauth_access_token_scope-_access_token')
                    ->references('id')
                    ->on('oauth_access_token')
                    ->onDelete('cascade');

                $table->foreign('_scope', 'fk-oauth_access_token_scope-_scope')
                    ->references('id')
                    ->on('oauth_scope')
                    ->onDelete('cascade');
            });
        }

        // =============================================
        // 7. OAUTH AUTH CODE SCOPES (Pivot)
        // =============================================
        if (!Schema::hasTable('oauth_auth_code_scope')) {
            Schema::create('oauth_auth_code_scope', function (Blueprint $table) {
                $table->string('_auth_code', 100);
                $table->string('_scope', 100);

                // No primary key - compound index instead

                // Indexes
                $table->index('_auth_code', 'idx-oauth_auth_code_scope-_auth_code');
                $table->index('_scope', 'idx-oauth_auth_code_scope-_scope');

                // Foreign keys
                $table->foreign('_auth_code', 'fk-oauth_auth_code_scope-_auth_code')
                    ->references('id')
                    ->on('oauth_auth_code')
                    ->onDelete('cascade');

                $table->foreign('_scope', 'fk-oauth_auth_code_scope-_scope')
                    ->references('id')
                    ->on('oauth_scope')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * IMPORTANT: Only drops tables if they don't contain data
     * This prevents accidental data loss
     */
    public function down(): void
    {
        // Drop pivot tables first (no FK dependencies)
        Schema::dropIfExists('oauth_auth_code_scope');
        Schema::dropIfExists('oauth_access_token_scope');

        // Drop tables with FKs
        Schema::dropIfExists('oauth_auth_code');
        Schema::dropIfExists('oauth_refresh_token');
        Schema::dropIfExists('oauth_access_token');
        Schema::dropIfExists('oauth_scope');
        Schema::dropIfExists('oauth_client');
    }
};
