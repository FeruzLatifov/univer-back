<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Yii2 compatibility fields to e_assignment table
     */
    public function up(): void
    {
        Schema::table('e_assignment', function (Blueprint $table) {
            // Core academic fields
            $table->foreignId('_curriculum')->nullable()->after('_subject')->comment('O\'quv reja');
            $table->string('_language', 64)->nullable()->after('_curriculum')->comment('Til');
            $table->string('_training_type', 64)->nullable()->after('_language')->comment('Ta\'lim turi (kunduzgi/kechki/sirtqi)');
            $table->foreignId('_subject_topic')->nullable()->after('_training_type')->comment('Mavzu ID');
            $table->string('_education_year', 64)->nullable()->after('_subject_topic')->comment('O\'quv yili');
            $table->string('_semester', 64)->nullable()->after('_education_year')->comment('Semestr');

            // Grading and submission fields
            $table->string('_marking_category', 64)->nullable()->after('max_score')->comment('Baholash kategoriyasi (oraliq/yakuniy/mustaqil)');
            $table->integer('attempt_count')->nullable()->after('deadline')->comment('Urinishlar soni (null = cheksiz)');

            // File management
            $table->json('files')->nullable()->after('allow_late')->comment('Topshiriq fayllari (ko\'p fayl)');

            // Ordering and publishing
            $table->integer('position')->default(0)->after('files')->comment('Tartiblash');
            $table->dateTime('published_at')->nullable()->after('updated_at')->comment('Nashr qilingan vaqt');

            // Indexes for performance
            $table->index('_curriculum');
            $table->index('_language');
            $table->index('_training_type');
            $table->index('_subject_topic');
            $table->index('_education_year');
            $table->index('_semester');
            $table->index('_marking_category');
            $table->index('published_at');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_assignment', function (Blueprint $table) {
            $table->dropIndex(['_curriculum']);
            $table->dropIndex(['_language']);
            $table->dropIndex(['_training_type']);
            $table->dropIndex(['_subject_topic']);
            $table->dropIndex(['_education_year']);
            $table->dropIndex(['_semester']);
            $table->dropIndex(['_marking_category']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['position']);

            $table->dropColumn([
                '_curriculum',
                '_language',
                '_training_type',
                '_subject_topic',
                '_education_year',
                '_semester',
                '_marking_category',
                'attempt_count',
                'files',
                'position',
                'published_at',
            ]);
        });
    }
};
