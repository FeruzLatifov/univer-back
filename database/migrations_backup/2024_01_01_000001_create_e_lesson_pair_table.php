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
        Schema::create('e_lesson_pair', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->comment('Juftlik raqami (1, 2, 3, ...)');
            $table->time('start_time')->comment('Boshlanish vaqti');
            $table->time('end_time')->comment('Tugash vaqti');
            $table->boolean('active')->default(true);

            $table->index('active');
            $table->index('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_lesson_pair');
    }
};
