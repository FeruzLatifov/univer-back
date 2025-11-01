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
        Schema::create('e_exam_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_exam')->comment('Imtihon');
            $table->foreignId('_student')->comment('Talaba');
            $table->decimal('score', 10, 2)->nullable()->comment('Ball');
            $table->integer('max_score')->default(100)->comment('Maksimal ball');
            $table->string('grade', 5)->nullable()->comment('Baho (5, 4, 3, 2)');
            $table->string('letter_grade', 2)->nullable()->comment('Harf bahosi (A, B, C, D, F)');
            $table->boolean('passed')->default(false)->comment('O\'tdimi');
            $table->boolean('attended')->default(true)->comment('Qatnashdimi');
            $table->text('comment')->nullable()->comment('Izoh');
            $table->dateTime('graded_at')->nullable()->comment('Baho qo\'yilgan vaqt');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_exam');
            $table->index('_student');
            $table->index('passed');
            $table->index('attended');
            $table->index('active');

            $table->unique(['_exam', '_student']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_exam_student');
    }
};
