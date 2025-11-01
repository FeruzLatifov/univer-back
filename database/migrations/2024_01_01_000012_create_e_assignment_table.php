<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_assignment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_subject')->comment('Fan');
            $table->foreignId('_group')->comment('Guruh');
            $table->foreignId('_employee')->comment('O\'qituvchi');
            $table->string('title', 512)->comment('Topshiriq nomi');
            $table->text('description')->nullable()->comment('Tavsif');
            $table->text('instructions')->nullable()->comment('Ko\'rsatma');
            $table->integer('max_score')->default(100)->comment('Maksimal ball');
            $table->dateTime('deadline')->comment('Topshirish muddati');
            $table->boolean('allow_late')->default(false)->comment('Kech topshirishga ruxsat');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_subject');
            $table->index('_group');
            $table->index('_employee');
            $table->index('deadline');
        });

        Schema::create('e_assignment_submission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_assignment')->comment('Topshiriq');
            $table->foreignId('_student')->comment('Talaba');
            $table->text('text_content')->nullable()->comment('Matn');
            $table->string('file_path', 512)->nullable()->comment('Fayl');
            $table->string('file_name', 256)->nullable()->comment('Fayl nomi');
            $table->dateTime('submitted_at')->nullable()->comment('Topshirilgan vaqt');
            $table->boolean('is_late')->default(false)->comment('Kech topshirildi');
            $table->decimal('score', 10, 2)->nullable()->comment('Ball');
            $table->integer('max_score')->default(100);
            $table->text('feedback')->nullable()->comment('Fikr');
            $table->dateTime('graded_at')->nullable()->comment('Baho qo\'yilgan vaqt');
            $table->string('status', 20)->default('pending')->comment('Holati');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_assignment');
            $table->index('_student');
            $table->index('status');
            $table->unique(['_assignment', '_student']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_assignment_submission');
        Schema::dropIfExists('e_assignment');
    }
};
