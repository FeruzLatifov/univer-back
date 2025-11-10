<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates e_student_test_answer table for student answers to test questions
     */
    public function up(): void
    {
        Schema::create('e_student_test_answer', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('_attempt')->comment('Attempt ID');
            $table->foreignId('_question')->comment('Savol ID');
            $table->foreignId('_answer')->nullable()->comment('Tanlangan javob ID (MC only)');

            // Answer Content
            $table->text('answer_text')->nullable()->comment('Text javob');
            $table->boolean('answer_boolean')->nullable()->comment('True/False javob');
            $table->text('selected_answers')->nullable()->comment('Multiple javoblar (JSON array)');

            // Scoring
            $table->decimal('points_earned', 8, 2)->nullable()->comment('Olingan ball');
            $table->decimal('points_possible', 8, 2)->nullable()->comment('Mumkin bo\'lgan ball');
            $table->boolean('is_correct')->nullable()->comment('To\'g\'ri/Noto\'g\'ri');

            // Manual Grading
            $table->boolean('manually_graded')->default(false)->comment('Manual baholangan');
            $table->foreignId('graded_by')->nullable()->comment('Baholovchi o\'qituvchi ID');
            $table->timestamp('graded_at')->nullable()->comment('Baholangan vaqt');
            $table->text('feedback')->nullable()->comment('Savol bo\'yicha izoh');

            // Metadata
            $table->timestamp('answered_at')->nullable()->comment('Javob berilgan vaqt');
            $table->boolean('active')->default(true)->comment('Faol');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('_attempt')->references('id')->on('e_student_test_attempt')->onDelete('cascade');
            $table->foreign('_question')->references('id')->on('e_subject_test_question')->onDelete('cascade');
            $table->foreign('_answer')->references('id')->on('e_subject_test_answer')->onDelete('set null');
            $table->foreign('graded_by')->references('id')->on('e_employee')->onDelete('set null');

            // Unique Constraint
            $table->unique(['_attempt', '_question'], 'unique_attempt_question');

            // Indexes
            $table->index('_attempt');
            $table->index('_question');
            $table->index('manually_graded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_student_test_answer');
    }
};
