<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks student activity on assignments/tasks
     */
    public function up(): void
    {
        Schema::create('e_student_task_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_assignment')->comment('Topshiriq ID');
            $table->foreignId('_student')->comment('Talaba ID');
            $table->string('activity_type', 50)->comment('Faollik turi (viewed, started, submitted, graded, returned)');
            $table->text('details')->nullable()->comment('Qo\'shimcha ma\'lumot (JSON)');
            $table->string('ip_address', 45)->nullable()->comment('IP manzil');
            $table->text('user_agent')->nullable()->comment('Browser ma\'lumoti');
            $table->timestamps();

            // Indexes
            $table->index(['_assignment', '_student']);
            $table->index('activity_type');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('_assignment')
                ->references('id')
                ->on('e_assignment')
                ->onDelete('cascade');

            $table->foreign('_student')
                ->references('id')
                ->on('e_student')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_student_task_activity');
    }
};
