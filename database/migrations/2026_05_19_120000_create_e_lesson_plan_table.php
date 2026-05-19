<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel-only table; not present in Yii2.
     * Stores a teacher's calendar plan (taqvimiy reja) — which topics are
     * scheduled for which dates in a given subject/group context.
     */
    public function up(): void
    {
        if (Schema::hasTable('e_lesson_plan')) {
            return;
        }

        Schema::create('e_lesson_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('_employee');
            $table->unsignedBigInteger('_subject');
            $table->unsignedBigInteger('_group')->nullable();
            $table->unsignedBigInteger('_topic');
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->unsignedSmallInteger('hours')->default(2);
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['_employee', '_subject', '_group'], 'e_lesson_plan_scope_idx');
            $table->index('_topic');
            $table->unique(['_employee', '_subject', '_group', '_topic'], 'e_lesson_plan_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_lesson_plan');
    }
};
