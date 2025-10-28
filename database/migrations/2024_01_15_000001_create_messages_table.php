<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create messages table for messaging system
 *
 * This table stores all messages between users (teachers, students, admins)
 * Supports: direct messages, broadcast, announcements
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Sender information
            $table->unsignedBigInteger('sender_id')->comment('ID of sender');
            $table->string('sender_type', 50)->comment('Type: teacher, student, admin');

            // Receiver information (can be null for broadcast to group)
            $table->unsignedBigInteger('receiver_id')->nullable()->comment('ID of receiver (null for broadcast)');
            $table->string('receiver_type', 50)->nullable()->comment('Type: teacher, student, admin, group');

            // Message content
            $table->string('subject', 500)->comment('Message subject/title');
            $table->text('body')->comment('Message content/body');

            // Message type
            $table->string('message_type', 50)->default('direct')->comment('Type: direct, broadcast, announcement');
            $table->string('priority', 20)->default('normal')->comment('Priority: low, normal, high, urgent');

            // Status
            $table->boolean('is_read')->default(false)->comment('Read status');
            $table->timestamp('read_at')->nullable()->comment('When was message read');

            // Attachments flag
            $table->boolean('has_attachments')->default(false)->comment('Does message have attachments');

            // Reply threading
            $table->unsignedBigInteger('parent_message_id')->nullable()->comment('Parent message for replies');
            $table->foreign('parent_message_id')->references('id')->on('messages')->onDelete('cascade');

            // Multilingual support
            $table->jsonb('_translations')->nullable()->comment('Translations for subject/body');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['sender_id', 'sender_type'], 'idx_sender');
            $table->index(['receiver_id', 'receiver_type'], 'idx_receiver');
            $table->index('created_at', 'idx_created_at');
            $table->index('is_read', 'idx_is_read');
            $table->index('message_type', 'idx_message_type');
            $table->index('priority', 'idx_priority');

            // Comment on table
            $table->comment('Main messages table for messaging system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
