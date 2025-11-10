<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates e_subject_test_answer table for answer options (multiple choice questions)
     */
    public function up(): void
    {
        Schema::create('e_subject_test_answer', function (Blueprint $table) {
            $table->id();

            // Reference
            $table->foreignId('_question')->comment('Savol ID');

            // Answer Content
            $table->text('answer_text')->comment('Javob matni');
            $table->string('image_path', 512)->nullable()->comment('Rasm (optional)');

            // Settings
            $table->integer('position')->default(0)->comment('Tartib');
            $table->boolean('is_correct')->default(false)->comment('To\'g\'ri javob');

            // Metadata
            $table->boolean('active')->default(true)->comment('Faol');
            $table->timestamps();

            // Foreign Key
            $table->foreign('_question')->references('id')->on('e_subject_test_question')->onDelete('cascade');

            // Indexes
            $table->index('_question');
            $table->index('is_correct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_test_answer');
    }
};
