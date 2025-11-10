<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table allows each university to override specific translations.
     * Use case: University A wants "Student" → "O'quvchi" instead of default "Talaba"
     *
     * Performance:
     * - Indexed for fast lookups
     * - Unique constraint prevents duplicates
     * - is_active allows temporary disable without deletion
     */
    public function up(): void
    {
        Schema::create('e_system_message_translation_override', function (Blueprint $table) {
            $table->id();
            $table->string('message', 255)->comment('Translation key (e.g., "Employee Information")');
            $table->string('language', 10)->comment('Language code (e.g., "uz-UZ", "oz-UZ")');
            $table->string('namespace', 50)->default('menu')->comment('Translation namespace (e.g., "menu", "messages")');
            $table->text('translation')->comment('Custom translation text');
            $table->boolean('is_custom')->default(true)->comment('True if set by university admin');
            $table->boolean('is_active')->default(true)->comment('False to temporarily disable without deletion');
            $table->timestamps();

            // Unique constraint: one override per message+language+namespace
            $table->unique(['message', 'language', 'namespace'], 'override_unique');

            // Indexes for performance
            $table->index(['language', 'namespace', 'is_active'], 'lookup_idx');
        });

        // Add table comment (PostgreSQL)
        DB::statement("COMMENT ON TABLE e_system_message_translation_override IS 'University-specific translation overrides for multi-tenant system'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_system_message_translation_override');
    }
};
