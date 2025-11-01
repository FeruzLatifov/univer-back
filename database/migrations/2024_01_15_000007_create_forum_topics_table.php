<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_topics table
 *
 * Discussion topics/threads in forum
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();

            // Category
            $table->unsignedBigInteger('category_id')->comment('Category ID');
            $table->foreign('category_id')->references('id')->on('forum_categories')->onDelete('cascade');

            // Author
            $table->unsignedBigInteger('author_id')->comment('Author user ID');
            $table->string('author_type', 50)->comment('Author type: teacher, student, admin');

            // Topic content
            $table->string('title', 500)->comment('Topic title');
            $table->string('slug', 500)->comment('URL-friendly slug');
            $table->text('body')->comment('Topic body/description');

            // Status & flags
            $table->boolean('is_pinned')->default(false)->comment('Pinned to top');
            $table->boolean('is_locked')->default(false)->comment('No new replies');
            $table->boolean('is_approved')->default(true)->comment('Approved by moderator');
            $table->boolean('is_featured')->default(false)->comment('Featured topic');

            // Tags
            $table->json('tags')->nullable()->comment('Topic tags array');

            // Stats (cached)
            $table->integer('views_count')->default(0)->comment('View count');
            $table->integer('posts_count')->default(0)->comment('Reply count');
            $table->integer('likes_count')->default(0)->comment('Total likes');

            // Last activity
            $table->unsignedBigInteger('last_post_id')->nullable()->comment('Last post ID');
            $table->unsignedBigInteger('last_post_author_id')->nullable()->comment('Last post author ID');
            $table->string('last_post_author_type', 50)->nullable()->comment('Last post author type');
            $table->timestamp('last_post_at')->nullable()->comment('Last post time');

            // Multilingual
            $table->jsonb('_translations')->nullable()->comment('Translations for title/body');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['category_id', 'created_at'], 'idx_category_created');
            $table->index('author_id', 'idx_author');
            $table->index('slug', 'idx_slug');
            $table->index('is_pinned', 'idx_pinned');
            $table->index('is_locked', 'idx_locked');
            $table->index('is_approved', 'idx_approved');
            $table->index('last_post_at', 'idx_last_post');
            $table->index('views_count', 'idx_views');

            // Full-text search
            $table->index(['title', 'body'], 'idx_fulltext');

            // Comment
            $table->comment('Forum topics/threads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_topics');
    }
};
