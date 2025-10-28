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
        Schema::create('e_subject', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Fan kodi (MATH101)');
            $table->string('name', 256)->comment('Fan nomi');
            $table->integer('credit')->default(0)->comment('Kredit soat');
            $table->string('_curriculum_subject_type', 11)->nullable()->comment('Fan turi');
            $table->foreignId('_department')->nullable()->comment('Kafedra');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
            $table->index('_department');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject');
    }
};
