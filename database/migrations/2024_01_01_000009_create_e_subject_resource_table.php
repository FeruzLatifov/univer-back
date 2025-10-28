<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('e_subject_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('_subject')->comment('Fan');
            $table->foreignId('_employee')->nullable()->comment('Yuklagan o\'qituvchi');
            $table->string('name', 256)->comment('Resurs nomi');
            $table->text('description')->nullable()->comment('Tavsif');
            $table->string('filename', 256)->comment('Fayl nomi');
            $table->string('path', 512)->comment('Fayl manzili');
            $table->string('mime_type', 100)->nullable()->comment('Fayl turi');
            $table->bigInteger('size')->nullable()->comment('Fayl hajmi (bytes)');
            $table->string('_resource_type', 11)->comment('Resurs turi (11=ma\'ruza, 12=amaliy, 13=topshiriq, 14=adabiyot, 15=imtihon)');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('_subject');
            $table->index('_employee');
            $table->index('_resource_type');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_subject_resource');
    }
};
