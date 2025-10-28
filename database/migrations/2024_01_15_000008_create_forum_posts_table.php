<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_posts table
 *
 * Posts/replies in forum topics
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();

            // Topic
            $table->unsignedBigInteger('topic_id')->comment('Topic ID');
            $table->foreign('topic_id')->references('id')->on('forum_topics')->onDelete('cascade');

            // Author
            $table->unsignedBigInteger('author_id')->comment('Author user ID');
            $table->string('author_type', 50)->comment('Author type: teacher, student, admin');

            // Post content
            $table->text('body')->comment('Post content');

            // Reply to another post (nested replies)
            $table->unsignedBigInteger('parent_post_id')->nullable()->comment('Parent post ID (for nested replies)');
            $table->foreign('parent_post_id')->references('id')->on('forum_posts')->onDelete('cascade');

            // Status & flags
            $table->boolean('is_approved')->default(true)->comment('Approved by moderator');
            $table->boolean('is_best_answer')->default(false)->comment('Marked as best answer');
            $table->boolean('is_edited')->default(false)->comment('Post was edited');
            $table->timestamp('edited_at')->nullable()->comment('Last edit time');
            $table->unsignedBigInteger('edited_by')->nullable()->comment('Who edited (moderator)');

            // Stats
            $table->integer('likes_count')->default(0)->comment('Total likes');

            // Multilingual
            $table->jsonb('_translations')->nullable()->comment('Translations for body');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['topic_id', 'created_at'], 'idx_topic_created');
            $table->index('author_id', 'idx_author');
            $table->index('parent_post_id', 'idx_parent');
            $table->index('is_approved', 'idx_approved');
            $table->index('is_best_answer', 'idx_best_answer');

            // Comment
            $table->comment('Forum posts/replies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
