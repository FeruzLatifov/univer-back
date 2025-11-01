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
        Schema::create('e_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_student')->comment('Talaba');
            $table->foreignId('_subject')->comment('Fan');
            $table->foreignId('_education_year')->nullable()->comment('O\'quv yili');
            $table->integer('_semester')->comment('Semestr');
            $table->string('_grade_type', 11)->comment('Baho turi (11=joriy, 12=oraliq, 13=yakuniy, 14=umumiy)');
            $table->decimal('grade', 10, 2)->comment('Baho');
            $table->integer('max_grade')->nullable()->default(100)->comment('Maksimal baho');
            $table->text('comment')->nullable()->comment('Izoh');
            $table->foreignId('_employee')->nullable()->comment('O\'qituvchi');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_student');
            $table->index('_subject');
            $table->index('_semester');
            $table->index('_grade_type');
            $table->index('_employee');
            $table->index('active');

            // Unique constraint: one grade per student per subject per type per semester
            $table->unique(['_student', '_subject', '_grade_type', '_semester', '_education_year'], 'unique_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_performance');
    }
};
