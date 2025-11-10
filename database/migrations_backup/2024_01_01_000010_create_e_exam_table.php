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
        Schema::create('e_exam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_subject')->comment('Fan');
            $table->foreignId('_group')->comment('Guruh');
            $table->foreignId('_education_year')->nullable()->comment('O\'quv yili');
            $table->integer('_semester')->comment('Semestr');
            $table->string('_exam_type', 11)->comment('Imtihon turi (11=oraliq, 12=yakuniy)');
            $table->dateTime('exam_date')->comment('Imtihon sanasi va vaqti');
            $table->foreignId('_employee')->nullable()->comment('Imtihon oluvchi');
            $table->foreignId('_auditorium')->nullable()->comment('Xona');
            $table->integer('duration')->nullable()->comment('Davomiyligi (minut)');
            $table->integer('max_score')->default(100)->comment('Maksimal ball');
            $table->string('status', 20)->default('scheduled')->comment('Holati (scheduled, in_progress, completed, cancelled)');
            $table->text('notes')->nullable()->comment('Izohlar');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_subject');
            $table->index('_group');
            $table->index('_semester');
            $table->index('_employee');
            $table->index('exam_date');
            $table->index('status');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_exam');
    }
};
