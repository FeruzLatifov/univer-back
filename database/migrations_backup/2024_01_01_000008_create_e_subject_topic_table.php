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
        Schema::create('e_subject_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_subject')->comment('Fan');
            $table->string('name', 512)->comment('Mavzu nomi');
            $table->text('content')->nullable()->comment('Mavzu mazmuni');
            $table->integer('order_number')->nullable()->comment('Tartibi');
            $table->integer('hours')->nullable()->comment('Soatlar');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_subject');
            $table->index('order_number');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_topic');
    }
};
