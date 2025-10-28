<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create message_recipients table
 *
 * This table stores recipients for broadcast messages
 * Allows tracking read status per recipient for group messages
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->id();

            // Message reference
            $table->unsignedBigInteger('message_id')->comment('Reference to message');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');

            // Recipient information
            $table->unsignedBigInteger('recipient_id')->comment('ID of recipient');
            $table->string('recipient_type', 50)->comment('Type: teacher, student, admin');

            // Status flags
            $table->boolean('is_read')->default(false)->comment('Has recipient read the message');
            $table->timestamp('read_at')->nullable()->comment('When was message read by recipient');
            $table->boolean('is_archived')->default(false)->comment('Is message archived by recipient');
            $table->boolean('is_starred')->default(false)->comment('Is message starred by recipient');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('message_id', 'idx_message_id');
            $table->index(['recipient_id', 'recipient_type'], 'idx_recipient');
            $table->index('is_read', 'idx_is_read');

            // Unique constraint - one recipient per message
            $table->unique(['message_id', 'recipient_id', 'recipient_type'], 'unique_message_recipient');

            // Comment
            $table->comment('Recipients for broadcast/group messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
    }
};
