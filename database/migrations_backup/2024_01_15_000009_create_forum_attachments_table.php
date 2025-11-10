<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create forum_attachments table
 *
 * File attachments for forum posts and topics
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_attachments', function (Blueprint $table) {
            $table->id();

            // Attachable (polymorphic - can attach to topic or post)
            $table->unsignedBigInteger('attachable_id')->comment('Topic or Post ID');
            $table->string('attachable_type', 50)->comment('ForumTopic or ForumPost');

            // Uploader
            $table->unsignedBigInteger('uploaded_by')->comment('User ID who uploaded');
            $table->string('uploader_type', 50)->comment('User type');

            // File info
            $table->string('file_name', 500)->comment('Original file name');
            $table->string('file_path', 1000)->comment('Storage path');
            $table->string('file_type', 100)->nullable()->comment('File extension');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type', 100)->nullable()->comment('MIME type');

            // Stats
            $table->integer('downloads_count')->default(0)->comment('Download count');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['attachable_type', 'attachable_id'], 'idx_attachable');
            $table->index('uploaded_by', 'idx_uploader');

            // Comment
            $table->comment('Forum attachments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_attachments');
    }
};
