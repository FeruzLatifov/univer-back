<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create e_student_meta table if it doesn't exist
 *
 * COMPATIBILITY: Yii2 ↔ Laravel
 * ================================
 *
 * If connecting to existing univer-yii2 database (hemis_401):
 * - Skip creation if table exists
 * - Use existing table structure
 *
 * If connecting to fresh database (test_401):
 * - Create table with full structure
 * - Add necessary indexes
 *
 * Safe to run on both databases!
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create if table doesn't exist (Yii2 compatibility)
        if (!Schema::hasTable('e_student_meta')) {
            Schema::create('e_student_meta', function (Blueprint $table) {
                $table->id();

                // Foreign keys
                $table->bigInteger('_student')->nullable();
                $table->bigInteger('_specialty')->nullable();
                $table->bigInteger('_group')->nullable();
                $table->bigInteger('_department')->nullable();

                // Classification codes
                $table->string('_education_type', 64)->nullable();
                $table->string('_education_form', 64)->nullable();
                $table->string('_education_year', 64)->nullable();
                $table->string('_level', 64)->nullable();
                $table->string('_payment_form', 64)->nullable();

                // Student status
                $table->string('student_status', 64)->nullable();
                $table->boolean('academic_leave')->default(false);
                $table->integer('year_of_entered')->nullable();

                // Active flag
                $table->boolean('active')->default(true);

                $table->timestamps();

                // Indexes for performance
                $table->index('_student');
                $table->index('_specialty');
                $table->index('_group');
                $table->index(['_student', 'active']);
            });

            echo "✅ Created e_student_meta table\n";
        } else {
            echo "⏭️  Skipped e_student_meta (table already exists - using Yii2 table)\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if we created it (check if it has Laravel's structure)
        if (Schema::hasTable('e_student_meta')) {
            // Safe rollback: only drop if this is a fresh Laravel installation
            // Don't drop if this is Yii2's original table
            $isLaravelTable = Schema::hasColumn('e_student_meta', 'id') &&
                             Schema::hasColumn('e_student_meta', 'created_at');

            if ($isLaravelTable) {
                Schema::dropIfExists('e_student_meta');
                echo "✅ Dropped e_student_meta table\n";
            } else {
                echo "⏭️  Skipped dropping e_student_meta (Yii2 original table)\n";
            }
        }
    }
};
