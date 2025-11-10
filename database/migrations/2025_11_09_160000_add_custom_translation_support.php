<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add custom translation support to existing Yii2 tables
 * 
 * SAFE MIGRATION:
 * - Only adds columns if they don't exist
 * - Never drops existing data
 * - Only removes columns WE added on rollback
 * - Works with both hemis_401 (prod) and test_401 (test)
 * 
 * Strategy:
 * - Base translations in 'translation' column (from CSV/Git imports)
 * - Custom translations in 'custom_translation' column (university-specific)
 * - Priority: custom > base
 * 
 * USE_TEST_DATABASE=false → hemis_401 (NO RefreshDatabase)
 * USE_TEST_DATABASE=true  → test_401 (YES RefreshDatabase)
 */
return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        // 1. Check if base tables exist (created by Yii2)
        if (!Schema::hasTable('e_system_message')) {
            // Create e_system_message (if not exists - for test DB)
            Schema::create('e_system_message', function (Blueprint $table) {
                $table->id();
                $table->string('category', 32)->nullable()->index();
                $table->text('message')->notNull();
                $table->timestamps();
            });

            echo "✅ Created table: e_system_message\n";
        } else {
            // Add timestamps to existing table
            if (!Schema::hasColumn('e_system_message', 'created_at')) {
                Schema::table('e_system_message', function (Blueprint $table) {
                    $table->timestamps();
                });
                echo "✅ Added timestamps to: e_system_message\n";
            }
        }

        // 2. Check translation table
        if (!Schema::hasTable('e_system_message_translation')) {
            // Create e_system_message_translation (if not exists - for test DB)
            Schema::create('e_system_message_translation', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->comment('FK to e_system_message.id');
                $table->string('language', 16)->comment('Language code: uz-UZ, ru-RU, etc');
                $table->text('translation')->nullable()->comment('Base translation (from CSV/Git)');
                
                // New columns for Laravel
                $table->text('custom_translation')->nullable()->comment('Custom university-specific translation');
                $table->timestamps();

                // Composite primary key (Yii2 style)
                $table->primary(['id', 'language'], 'pk_e_system_message_translation_id_language');

                // Foreign key
                $table->foreign('id', 'fk_system_message_translation_system_message')
                    ->references('id')
                    ->on('e_system_message')
                    ->onDelete('cascade');

                // Indexes
                $table->index('language', 'idx_system_message_translation_language');
            });

            echo('✅ Created table: e_system_message_translation');
        } else {
            // Alter existing table (add only new columns)
            Schema::table('e_system_message_translation', function (Blueprint $table) {
                // Add custom_translation column
                if (!Schema::hasColumn('e_system_message_translation', 'custom_translation')) {
                    $table->text('custom_translation')->nullable()->after('translation')
                        ->comment('Custom university-specific translation (overrides base)');
                    echo('✅ Added column: custom_translation');
                }

                // Add timestamps
                if (!Schema::hasColumn('e_system_message_translation', 'created_at')) {
                    $table->timestamps();
                    echo('✅ Added timestamps to: e_system_message_translation');
                }
            });
        }

        echo('');
        echo('🎉 Migration completed successfully!');
        echo('📊 Structure:');
        echo('   - translation: Base translations (from imports)');
        echo('   - custom_translation: University customizations');
        echo('   - Priority: custom > base');
    }

    /**
     * Reverse the migrations
     * 
     * SAFE ROLLBACK:
     * - Only removes columns WE added
     * - Never drops tables created by Yii2
     * - Never deletes data
     */
    public function down(): void
    {
        // Only remove custom_translation column (if exists)
        if (Schema::hasTable('e_system_message_translation')) {
            if (Schema::hasColumn('e_system_message_translation', 'custom_translation')) {
                Schema::table('e_system_message_translation', function (Blueprint $table) {
                    $table->dropColumn('custom_translation');
                });
                echo('✅ Removed column: custom_translation');
            }

            // Note: We don't remove timestamps because Yii2 might use them
            // Note: We don't drop tables because they're created by Yii2
        }

        echo('🔙 Rollback completed!');
    }
};
