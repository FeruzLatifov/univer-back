<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create message_attachments table
 *
 * Stores file attachments for messages
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();

            // Message reference
            $table->unsignedBigInteger('message_id')->comment('Reference to message');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');

            // File information
            $table->string('file_name', 500)->comment('Original file name');
            $table->string('file_path', 1000)->comment('Storage path');
            $table->string('file_type', 100)->nullable()->comment('File extension');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type', 100)->nullable()->comment('MIME type');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('message_id', 'idx_message_id');

            // Comment
            $table->comment('File attachments for messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
