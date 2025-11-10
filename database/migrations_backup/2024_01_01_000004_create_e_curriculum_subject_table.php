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
        Schema::create('e_curriculum_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_curriculum')->comment('O\'quv reja');
            $table->foreignId('_subject')->comment('Fan');
            $table->integer('_semester')->comment('Semestr');
            $table->string('_curriculum_subject_type', 11)->nullable()->comment('Fan turi (majburiy/tanlov)');
            $table->integer('credit_hours')->nullable()->comment('Kredit soat');
            $table->integer('lecture_hours')->nullable()->comment('Ma\'ruza soati');
            $table->integer('practice_hours')->nullable()->comment('Amaliyot soati');
            $table->integer('lab_hours')->nullable()->comment('Laboratoriya soati');
            $table->boolean('active')->default(true);

            $table->index('_curriculum');
            $table->index('_subject');
            $table->index('_semester');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_curriculum_subject');
    }
};
