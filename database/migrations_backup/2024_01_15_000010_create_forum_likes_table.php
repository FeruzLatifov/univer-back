<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_likes table
 *
 * Likes for forum topics and posts
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_likes', function (Blueprint $table) {
            $table->id();

            // Likeable (polymorphic - can like topic or post)
            $table->unsignedBigInteger('likeable_id')->comment('Topic or Post ID');
            $table->string('likeable_type', 50)->comment('ForumTopic or ForumPost');

            // User who liked
            $table->unsignedBigInteger('user_id')->comment('User ID');
            $table->string('user_type', 50)->comment('User type: teacher, student, admin');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint - user can like once
            $table->unique(['likeable_type', 'likeable_id', 'user_id', 'user_type'], 'unique_like');

            // Indexes
            $table->index(['likeable_type', 'likeable_id'], 'idx_likeable');
            $table->index(['user_id', 'user_type'], 'idx_user');

            // Comment
            $table->comment('Forum likes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_likes');
    }
};
