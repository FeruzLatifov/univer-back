<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tarjima tizimi uchun jadvallar:
     * - e_system_message: Asl matnlar (source messages)
     * - e_system_message_translation: Tarjimalar (translations)
     *
     * Yii2 bilan to'liq compatible!
     */
    public function up(): void
    {
        // Asl matnlar jadvali
        Schema::create('e_system_message', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->nullable()->index();
            $table->text('message')->notNullable();

            $table->unique(['category', 'message'], 'unique_message');
        });

        // Tarjimalar jadvali
        Schema::create('e_system_message_translation', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('language', 16)->notNullable();
            $table->text('translation')->nullable();

            $table->primary(['id', 'language']);
            $table->foreign('id')->references('id')->on('e_system_message')
                ->onDelete('cascade');

            $table->index('language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_system_message_translation');
        Schema::dropIfExists('e_system_message');
    }
};
