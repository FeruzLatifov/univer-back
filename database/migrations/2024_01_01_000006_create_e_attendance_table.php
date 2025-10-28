<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('e_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_student')->comment('Talaba');
            $table->foreignId('_subject_schedule')->comment('Dars jadvali');
            $table->date('lesson_date')->comment('Dars sanasi');
            $table->string('_attendance_type', 11)->comment('Davomat holati (11=kelgan, 12=kelmagan, 13=kech, 14=sababli)');
            $table->text('reason')->nullable()->comment('Sabab');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_student');
            $table->index('_subject_schedule');
            $table->index('lesson_date');
            $table->index('_attendance_type');
            $table->index('active');

            // Unique constraint: one attendance record per student per lesson
            $table->unique(['_student', '_subject_schedule', 'lesson_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_attendance');
    }
};
