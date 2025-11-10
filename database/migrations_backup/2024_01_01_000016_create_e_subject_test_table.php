<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates e_subject_test table for managing tests/quizzes
     */
    public function up(): void
    {
        Schema::create('e_subject_test', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('_subject')->comment('Fan ID');
            $table->foreignId('_employee')->comment('O\'qituvchi ID');
            $table->foreignId('_group')->nullable()->comment('Guruh ID');
            $table->foreignId('_subject_topic')->nullable()->comment('Mavzu ID');
            $table->foreignId('_curriculum')->nullable()->comment('O\'quv reja ID');
            $table->string('_education_year', 64)->nullable()->comment('O\'quv yili');
            $table->string('_semester', 64)->nullable()->comment('Semestr');

            // Test Info
            $table->string('title', 256)->comment('Test nomi');
            $table->text('description')->nullable()->comment('Tavsif');
            $table->text('instructions')->nullable()->comment('Ko\'rsatmalar');

            // Settings
            $table->integer('duration')->nullable()->comment('Davomiylik (daqiqada)');
            $table->decimal('passing_score', 5, 2)->nullable()->comment('O\'tish balli (%)');
            $table->decimal('max_score', 8, 2)->default(100)->comment('Maksimal ball');

            // Question Settings
            $table->integer('question_count')->default(0)->comment('Savollar soni');
            $table->boolean('randomize_questions')->default(false)->comment('Savollarni aralashtirish');
            $table->boolean('randomize_answers')->default(false)->comment('Javoblarni aralashtirish');
            $table->boolean('show_correct_answers')->default(true)->comment('To\'g\'ri javoblarni ko\'rsatish');

            // Attempt Settings
            $table->integer('attempt_limit')->default(1)->comment('Urinishlar soni');
            $table->boolean('allow_review')->default(true)->comment('Qayta ko\'rishga ruxsat');

            // Scheduling
            $table->timestamp('start_date')->nullable()->comment('Boshlanish vaqti');
            $table->timestamp('end_date')->nullable()->comment('Tugash vaqti');

            // Status
            $table->boolean('is_published')->default(false)->comment('Nashr qilingan');
            $table->timestamp('published_at')->nullable()->comment('Nashr vaqti');

            // Metadata
            $table->boolean('active')->default(true)->comment('Faol');
            $table->integer('position')->default(0)->comment('Tartib');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('_subject')->references('id')->on('curriculum_subject')->onDelete('cascade');
            $table->foreign('_employee')->references('id')->on('e_employee')->onDelete('cascade');
            $table->foreign('_group')->references('id')->on('e_group')->onDelete('set null');
            $table->foreign('_subject_topic')->references('id')->on('e_subject_topic')->onDelete('set null');

            // Indexes
            $table->index('_subject');
            $table->index('_employee');
            $table->index('_group');
            $table->index('is_published');
            $table->index(['start_date', 'end_date']);
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_test');
    }
};
