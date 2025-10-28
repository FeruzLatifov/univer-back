<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need translations
     */
    protected array $tables = [
        // University structure
        'e_university',
        'e_department',

        // Reference data (Handbooks)
        'h_education_type',
        'h_education_form',
        'h_education_year',
        'h_course',
        'h_semester',
        'h_student_status',
        'h_employee_type',
        'h_employee_status',
        'h_position',
        'h_academic_degree',
        'h_academic_rank',
        'h_subject',
        'h_subject_topic',
        'h_room',
        'h_building',
        'h_country',
        'h_region',
        'h_district',
        'h_soato',
        'h_citizenship',
        'h_nationality',
        'h_locality_type',

        // Curriculum
        'e_curriculum',
        'e_curriculum_subject',

        // Subjects & Groups
        'e_subject',
        'e_group',

        // Finance
        'h_payment_type',
        'h_contract_type',

        // Exams & Grades
        'h_exam_type',
        'h_grade_type',
        'h_marking_system',

        // Other
        'h_document_type',
        'h_decree_type',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, '_translations')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->jsonb('_translations')->nullable()->after('name');
                    $table->index('_translations');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, '_translations')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex([$table->getTable() . '__translations_index']);
                    $table->dropColumn('_translations');
                });
            }
        }
    }
};
