<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_subscriptions table
 *
 * User subscriptions to forum categories and topics
 * Get notified when new posts are added
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_subscriptions', function (Blueprint $table) {
            $table->id();

            // Subscribable (polymorphic - can subscribe to category or topic)
            $table->unsignedBigInteger('subscribable_id')->comment('Category or Topic ID');
            $table->string('subscribable_type', 50)->comment('ForumCategory or ForumTopic');

            // Subscriber
            $table->unsignedBigInteger('user_id')->comment('User ID');
            $table->string('user_type', 50)->comment('User type: teacher, student, admin');

            // Notification preferences
            $table->boolean('notify_email')->default(true)->comment('Send email notification');
            $table->boolean('notify_push')->default(true)->comment('Send push notification');

            // Last notification sent
            $table->timestamp('last_notified_at')->nullable()->comment('Last notification time');

            // Timestamps
            $table->timestamps();

            // Unique constraint - user can subscribe once
            $table->unique(['subscribable_type', 'subscribable_id', 'user_id', 'user_type'], 'unique_subscription');

            // Indexes
            $table->index(['subscribable_type', 'subscribable_id'], 'idx_subscribable');
            $table->index(['user_id', 'user_type'], 'idx_user');

            // Comment
            $table->comment('Forum subscriptions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_subscriptions');
    }
};
