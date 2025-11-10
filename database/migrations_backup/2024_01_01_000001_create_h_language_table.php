<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAFE/IDEMPOTENT: Only creates table and inserts if not exists.
     */
    public function up(): void
    {
        // Guard: if table exists, do nothing
        if (Schema::hasTable('h_language')) {
            return;
        }

        Schema::create('h_language', function (Blueprint $table) {
            $table->string('code', 64)->primary();
            $table->string('name', 256);
            $table->string('native_name', 256)->nullable();
            $table->integer('position')->default(0);
            $table->boolean('active')->default(true);
            $table->string('_parent', 64)->nullable();
            $table->jsonb('_translations')->nullable();
            $table->jsonb('_options')->nullable();
            $table->timestamps();

            $table->index('active');
            $table->index('position');
        });

        // Add comment
        DB::statement("COMMENT ON TABLE h_language IS 'Ta''lim tillari'");

        // Insert default languages (insert-ignore semantics)
        $defaultLanguages = [
            [
                'code' => 'uz',
                'name' => 'O\'zbek',
                'native_name' => 'O\'zbekcha',
                'position' => 1,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'O\'zbek'],
                    'ru' => ['name' => 'Узбекский'],
                    'en' => ['name' => 'Uzbek'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ru',
                'name' => 'Русский',
                'native_name' => 'Русский',
                'position' => 3,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'Rus'],
                    'ru' => ['name' => 'Русский'],
                    'en' => ['name' => 'Russian'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'position' => 4,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'Ingliz'],
                    'ru' => ['name' => 'Английский'],
                    'en' => ['name' => 'English'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultLanguages as $lang) {
            // Insert-ignore: only if code doesn't exist
            if (!DB::table('h_language')->where('code', $lang['code'])->exists()) {
                DB::table('h_language')->insert($lang);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_language');
    }
};
