<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates e_student_test_attempt table for student test attempts
     */
    public function up(): void
    {
        Schema::create('e_student_test_attempt', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('_test')->comment('Test ID');
            $table->foreignId('_student')->comment('Talaba ID');

            // Attempt Info
            $table->integer('attempt_number')->default(1)->comment('Urinish raqami');
            $table->string('status', 50)->default('started')->comment('Status');
            // Status: 'started', 'in_progress', 'submitted', 'graded', 'abandoned'

            // Timing
            $table->timestamp('started_at')->nullable()->comment('Boshlangan vaqt');
            $table->timestamp('submitted_at')->nullable()->comment('Topshirilgan vaqt');
            $table->timestamp('graded_at')->nullable()->comment('Baholangan vaqt');
            $table->integer('duration_seconds')->nullable()->comment('Davomiyligi (soniyada)');

            // Scoring
            $table->decimal('total_score', 8, 2)->nullable()->comment('Olingan ball');
            $table->decimal('max_score', 8, 2)->nullable()->comment('Maksimal ball');
            $table->decimal('percentage', 5, 2)->nullable()->comment('Foiz');
            $table->boolean('passed')->nullable()->comment('O\'tdimi');

            // Auto vs Manual
            $table->decimal('auto_graded_score', 8, 2)->nullable()->comment('Auto-graded ball');
            $table->decimal('manual_graded_score', 8, 2)->nullable()->comment('Manual graded ball');

            // Feedback
            $table->text('feedback')->nullable()->comment('O\'qituvchi izohi');

            // Metadata
            $table->string('ip_address', 45)->nullable()->comment('IP address');
            $table->text('user_agent')->nullable()->comment('Browser info');
            $table->boolean('active')->default(true)->comment('Faol');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('_test')->references('id')->on('e_subject_test')->onDelete('cascade');
            $table->foreign('_student')->references('id')->on('e_student')->onDelete('cascade');

            // Unique Constraint
            $table->unique(['_test', '_student', 'attempt_number'], 'unique_test_student_attempt');

            // Indexes
            $table->index('_test');
            $table->index('_student');
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_student_test_attempt');
    }
};
