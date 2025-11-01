<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAFE/IDEMPOTENT: Inserts all standard languages if missing.
     * Matches existing Yii2 schema (no native_name column).
     * Never modifies existing rows.
     */
    public function up(): void
    {
        // Guard: if table doesn't exist, return (no-op)
        if (!Schema::hasTable('h_language')) {
            return;
        }

        // Insert standard languages (insert-ignore semantics)
        $languages = [
            [
                'code' => 'uz',
                'name' => "O'zbek",
                'position' => 1,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => "O'zbek"],
                    'ru' => ['name' => 'Узбекский'],
                    'en' => ['name' => 'Uzbek'],
                ]),
                '_options' => json_encode(['version' => 2]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'oz',
                'name' => 'Ўзбек',
                'position' => 2,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'Ўзбек (кирилл)'],
                    'oz' => ['name' => 'Ўзбек'],
                    'ru' => ['name' => 'Узбекский (кириллица)'],
                    'en' => ['name' => 'Uzbek (Cyrillic)'],
                ]),
                '_options' => json_encode(['version' => 2]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ru',
                'name' => 'Русский',
                'position' => 3,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'Rus'],
                    'ru' => ['name' => 'Русский'],
                    'en' => ['name' => 'Russian'],
                ]),
                '_options' => json_encode(['version' => 2]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'position' => 4,
                'active' => true,
                '_parent' => null,
                '_translations' => json_encode([
                    'uz' => ['name' => 'Ingliz'],
                    'ru' => ['name' => 'Английский'],
                    'en' => ['name' => 'English'],
                ]),
                '_options' => json_encode(['version' => 2]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($languages as $lang) {
            // Insert-ignore: only if code doesn't exist
            if (!DB::table('h_language')->where('code', $lang['code'])->exists()) {
                DB::table('h_language')->insert($lang);
            }
        }
    }

    /**
     * Reverse the migrations.
     * SAFE: No destructive delete (no-op) to keep production-safe.
     */
    public function down(): void
    {
        // No-op: never delete in production for safety
    }
};
