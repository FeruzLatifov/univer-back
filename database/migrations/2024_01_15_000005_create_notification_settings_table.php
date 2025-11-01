<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create notification_settings table
 *
 * User preferences for notification delivery channels
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();

            // User information
            $table->unsignedBigInteger('user_id')->comment('User ID');
            $table->string('user_type', 50)->comment('User type: teacher, student, admin');

            // Notification type
            $table->string('notification_type', 100)->comment('Type: assignment_due, grade_posted, etc.');

            // Channel preferences
            $table->boolean('email_enabled')->default(true)->comment('Receive via email');
            $table->boolean('push_enabled')->default(true)->comment('Receive via push notification');
            $table->boolean('sms_enabled')->default(false)->comment('Receive via SMS');
            $table->boolean('in_app_enabled')->default(true)->comment('Show in-app notification');

            // Timestamps
            $table->timestamps();

            // Unique constraint - one setting per user per notification type
            $table->unique(['user_id', 'user_type', 'notification_type'], 'unique_user_notification_setting');

            // Indexes
            $table->index(['user_id', 'user_type'], 'idx_user');

            // Comment
            $table->comment('User notification preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
