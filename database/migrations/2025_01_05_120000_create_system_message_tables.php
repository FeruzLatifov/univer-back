<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates translation management tables similar to Yii2:
     * - e_system_message: Base messages (keys)
     * - e_system_message_translation: Translations for each language
     */
    public function up(): void
    {
        // Base messages table
        Schema::create('e_system_message', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->nullable()->index();
            $table->text('message');

            $table->unique(['category', 'message'], 'unique_category_message');
            $table->index('message');
        });

        // Translations table
        Schema::create('e_system_message_translation', function (Blueprint $table) {
            $table->foreignId('id')->constrained('e_system_message')->onDelete('cascade');
            $table->string('language', 16);
            $table->text('translation')->nullable();

            $table->primary(['id', 'language']);
            $table->index('language');
            $table->unique(['language', 'id'], 'unique_lang_id');
        });

        // Optional: Override table for university-specific translations
        Schema::create('e_system_message_translation_override', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('e_system_message')->onDelete('cascade');
            $table->unsignedInteger('university_id')->nullable();
            $table->string('language', 16);
            $table->text('translation');

            $table->unique(['message_id', 'university_id', 'language'], 'unique_override');
            $table->index(['university_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_system_message_translation_override');
        Schema::dropIfExists('e_system_message_translation');
        Schema::dropIfExists('e_system_message');
    }
};
