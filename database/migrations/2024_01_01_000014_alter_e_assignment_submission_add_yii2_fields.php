<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Yii2 compatibility fields to e_assignment_submission table
     */
    public function up(): void
    {
        Schema::table('e_assignment_submission', function (Blueprint $table) {
            // Academic context fields
            $table->foreignId('_curriculum')->nullable()->after('_assignment')->comment('O\'quv reja');
            $table->foreignId('_subject')->nullable()->after('_curriculum')->comment('Fan');
            $table->string('_training_type', 64)->nullable()->after('_subject')->comment('Ta\'lim turi');
            $table->string('_education_year', 64)->nullable()->after('_training_type')->comment('O\'quv yili');
            $table->string('_semester', 64)->nullable()->after('_education_year')->comment('Semestr');
            $table->foreignId('_group')->nullable()->after('_student')->comment('Guruh');
            $table->foreignId('_employee')->nullable()->after('_group')->comment('O\'qituvchi (baholovchi)');

            // Multiple files support
            $table->json('files')->nullable()->after('file_name')->comment('Yuborilgan fayllar (ko\'p fayl)');

            // Attempt tracking
            $table->integer('attempt_number')->default(1)->after('files')->comment('Urinish raqami');

            // Ordering
            $table->integer('position')->default(0)->after('is_late')->comment('Tartiblash');

            // Additional metadata
            $table->dateTime('viewed_at')->nullable()->after('graded_at')->comment('O\'qituvchi ko\'rgan vaqt');
            $table->dateTime('returned_at')->nullable()->after('viewed_at')->comment('Qaytarilgan vaqt (revision uchun)');

            // Indexes for performance
            $table->index('_curriculum');
            $table->index('_subject');
            $table->index('_training_type');
            $table->index('_education_year');
            $table->index('_semester');
            $table->index('_group');
            $table->index('_employee');
            $table->index('attempt_number');
            $table->index('viewed_at');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_assignment_submission', function (Blueprint $table) {
            $table->dropIndex(['_curriculum']);
            $table->dropIndex(['_subject']);
            $table->dropIndex(['_training_type']);
            $table->dropIndex(['_education_year']);
            $table->dropIndex(['_semester']);
            $table->dropIndex(['_group']);
            $table->dropIndex(['_employee']);
            $table->dropIndex(['attempt_number']);
            $table->dropIndex(['viewed_at']);
            $table->dropIndex(['submitted_at']);

            $table->dropColumn([
                '_curriculum',
                '_subject',
                '_training_type',
                '_education_year',
                '_semester',
                '_group',
                '_employee',
                'files',
                'attempt_number',
                'position',
                'viewed_at',
                'returned_at',
            ]);
        });
    }
};
