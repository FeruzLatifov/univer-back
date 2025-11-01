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
        Schema::create('e_system', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('System setting code');
            $table->text('value')->nullable()->comment('Setting value');
            $table->string('type')->default('string')->comment('Value type: string, text, image, boolean, integer');
            $table->string('group')->nullable()->comment('Setting group');
            $table->text('description')->nullable()->comment('Setting description');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Insert default system settings
        DB::table('e_system')->insert([
            [
                'code' => 'system_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
                'description' => 'System logo path',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'system_name',
                'value' => 'UNIVER',
                'type' => 'string',
                'group' => 'general',
                'description' => 'System name',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'system_description',
                'value' => 'Universitet Boshqaruv Tizimi',
                'type' => 'string',
                'group' => 'general',
                'description' => 'System description',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_system');
    }
};
