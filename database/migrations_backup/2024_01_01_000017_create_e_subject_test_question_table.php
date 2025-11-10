<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates e_subject_test_question table for test questions
     */
    public function up(): void
    {
        Schema::create('e_subject_test_question', function (Blueprint $table) {
            $table->id();

            // Reference
            $table->foreignId('_test')->comment('Test ID');

            // Question Content
            $table->text('question_text')->comment('Savol matni');
            $table->string('question_type', 50)->comment('Savol turi');
            // Types: 'multiple_choice', 'true_false', 'short_answer', 'essay'

            // Scoring
            $table->decimal('points', 8, 2)->default(1)->comment('Ball');

            // Settings
            $table->integer('position')->default(0)->comment('Tartib');
            $table->boolean('is_required')->default(true)->comment('Majburiy');

            // Multiple Choice specific
            $table->text('correct_answers')->nullable()->comment('To\'g\'ri javoblar (JSON array of IDs)');
            $table->boolean('allow_multiple')->default(false)->comment('Ko\'p javobga ruxsat');

            // Short Answer specific
            $table->text('correct_answer_text')->nullable()->comment('To\'g\'ri javob matni');
            $table->boolean('case_sensitive')->default(false)->comment('Katta-kichik harf farqi');

            // True/False specific
            $table->boolean('correct_answer_boolean')->nullable()->comment('To\'g\'ri javob (true/false)');

            // Essay/Open-ended
            $table->integer('word_limit')->nullable()->comment('So\'z cheklovi');

            // Additional
            $table->text('explanation')->nullable()->comment('Tushuntirish');
            $table->string('image_path', 512)->nullable()->comment('Rasm (optional)');

            // Metadata
            $table->boolean('active')->default(true)->comment('Faol');
            $table->timestamps();

            // Foreign Key
            $table->foreign('_test')->references('id')->on('e_subject_test')->onDelete('cascade');

            // Indexes
            $table->index('_test');
            $table->index('question_type');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_test_question');
    }
};
