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
        Schema::create('e_curriculum', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256)->comment('O\'quv reja nomi');
            $table->foreignId('_specialty')->comment('Mutaxassislik');
            $table->string('_education_type', 11)->comment('Ta\'lim turi');
            $table->string('_education_form', 11)->comment('Ta\'lim shakli');
            $table->foreignId('_education_year')->nullable()->comment('O\'quv yili');
            $table->integer('year')->nullable()->comment('Kurs (1,2,3,4)');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
            $table->index('_specialty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_curriculum');
    }
};
