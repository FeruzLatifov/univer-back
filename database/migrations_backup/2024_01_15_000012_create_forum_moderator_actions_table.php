<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_moderator_actions table
 *
 * Log of moderator actions (approve, delete, lock, pin, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_moderator_actions', function (Blueprint $table) {
            $table->id();

            // Moderator
            $table->unsignedBigInteger('moderator_id')->comment('Moderator user ID');
            $table->string('moderator_type', 50)->comment('Usually: teacher or admin');

            // Target (polymorphic - can be topic or post)
            $table->unsignedBigInteger('target_id')->comment('Topic or Post ID');
            $table->string('target_type', 50)->comment('ForumTopic or ForumPost');

            // Action
            $table->string('action', 50)->comment('approve, reject, delete, lock, unlock, pin, unpin, feature, edit');
            $table->text('reason')->nullable()->comment('Reason for action');
            $table->json('metadata')->nullable()->comment('Additional data (before/after values)');

            // IP & User Agent
            $table->string('ip_address', 45)->nullable()->comment('Moderator IP');
            $table->string('user_agent', 500)->nullable()->comment('Browser info');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('moderator_id', 'idx_moderator');
            $table->index(['target_type', 'target_id'], 'idx_target');
            $table->index('action', 'idx_action');
            $table->index('created_at', 'idx_created');

            // Comment
            $table->comment('Forum moderator actions log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_moderator_actions');
    }
};
