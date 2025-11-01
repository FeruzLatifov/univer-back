<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create notifications table
 *
 * System-generated notifications for users
 * Examples: assignment due, grade posted, test available, etc.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Recipient information
            $table->unsignedBigInteger('user_id')->comment('User ID');
            $table->string('user_type', 50)->comment('User type: teacher, student, admin');

            // Notification content
            $table->string('type', 100)->comment('Notification type: assignment_due, grade_posted, etc.');
            $table->string('title', 500)->comment('Notification title');
            $table->text('message')->comment('Notification message');

            // Related entity (optional)
            $table->string('entity_type', 50)->nullable()->comment('Related entity type: assignment, test, grade');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Related entity ID');

            // Action URL (optional)
            $table->string('action_url', 1000)->nullable()->comment('URL to navigate when clicked');
            $table->string('action_text', 100)->nullable()->comment('Action button text');

            // Status
            $table->boolean('is_read')->default(false)->comment('Is notification read');
            $table->timestamp('read_at')->nullable()->comment('When was notification read');

            // Priority
            $table->string('priority', 20)->default('normal')->comment('Priority: low, normal, high, urgent');

            // Delivery tracking
            $table->boolean('sent_via_email')->default(false)->comment('Was email sent');
            $table->boolean('sent_via_push')->default(false)->comment('Was push notification sent');
            $table->timestamp('email_sent_at')->nullable()->comment('When email was sent');
            $table->timestamp('push_sent_at')->nullable()->comment('When push was sent');

            // Multilingual support
            $table->jsonb('_translations')->nullable()->comment('Translations for title/message');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('Notification expiration time');

            // Indexes for performance
            $table->index(['user_id', 'user_type'], 'idx_user');
            $table->index('is_read', 'idx_is_read');
            $table->index('type', 'idx_type');
            $table->index('created_at', 'idx_created_at');
            $table->index('priority', 'idx_priority');
            $table->index(['entity_type', 'entity_id'], 'idx_entity');

            // Comment
            $table->comment('System notifications for users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
