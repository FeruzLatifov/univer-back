<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_categories table
 *
 * Categories for forum topics (Math, Physics, General Discussion, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();

            // Category info
            $table->string('name', 200)->comment('Category name');
            $table->string('slug', 200)->unique()->comment('URL-friendly slug');
            $table->text('description')->nullable()->comment('Category description');
            $table->string('icon', 50)->nullable()->comment('Icon name (lucide-react)');
            $table->string('color', 20)->default('blue')->comment('Category color');

            // Ordering & visibility
            $table->integer('order')->default(0)->comment('Display order (lower first)');
            $table->boolean('is_active')->default(true)->comment('Is category visible');
            $table->boolean('is_locked')->default(false)->comment('No new topics allowed');

            // Permissions
            $table->boolean('requires_approval')->default(false)->comment('Posts need approval');
            $table->json('allowed_user_types')->nullable()->comment('Who can post: [teacher, student, admin]');

            // Parent category (for subcategories)
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent category ID');
            $table->foreign('parent_id')->references('id')->on('forum_categories')->onDelete('cascade');

            // Stats (cached for performance)
            $table->integer('topics_count')->default(0)->comment('Total topics');
            $table->integer('posts_count')->default(0)->comment('Total posts');
            $table->unsignedBigInteger('last_post_id')->nullable()->comment('Last post ID');
            $table->timestamp('last_post_at')->nullable()->comment('Last post time');

            // Multilingual
            $table->jsonb('_translations')->nullable()->comment('Translations for name/description');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('slug', 'idx_slug');
            $table->index('order', 'idx_order');
            $table->index('is_active', 'idx_is_active');
            $table->index('parent_id', 'idx_parent_id');

            // Comment
            $table->comment('Forum categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_categories');
    }
};
