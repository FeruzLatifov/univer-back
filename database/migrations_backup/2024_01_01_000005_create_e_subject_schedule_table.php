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
        Schema::create('e_subject_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_subject')->comment('Fan');
            $table->foreignId('_group')->comment('Guruh');
            $table->foreignId('_employee')->comment('O\'qituvchi');
            $table->foreignId('_lesson_pair')->comment('Dars vaqti');
            $table->foreignId('_auditorium')->nullable()->comment('Xona');
            $table->integer('_semester')->comment('Semestr');
            $table->foreignId('_education_year')->nullable()->comment('O\'quv yili');
            $table->integer('week')->comment('Hafta kuni (1-6)');
            $table->string('_training_type', 11)->nullable()->comment('Mashg\'ulot turi (ma\'ruza/amaliy)');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_subject');
            $table->index('_group');
            $table->index('_employee');
            $table->index('_semester');
            $table->index('week');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_schedule');
    }
};
