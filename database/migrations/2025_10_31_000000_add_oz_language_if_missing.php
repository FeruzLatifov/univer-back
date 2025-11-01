<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Idempotent: Inserts 'oz' language only if table exists and row missing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('h_language')) {
            return; // table not created yet, another migration will handle defaults
        }

        $exists = DB::table('h_language')->where('code', 'oz')->exists();
        if ($exists) {
            return; // already present
        }

        DB::table('h_language')->insert([
            'code' => 'oz',
            'name' => 'Ўзбек',
            'native_name' => 'Ўзбекча',
            'position' => 2,
            'active' => true,
            '_parent' => null,
            '_translations' => json_encode([
                'uz' => ['name' => "O'zbek (krill)"],
                'ru' => ['name' => 'Узбекский (кириллица)'],
                'en' => ['name' => 'Uzbek (Cyrillic)'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     * No-op for safety in production.
     */
    public function down(): void
    {
        // Intentionally left blank to avoid destructive deletes in production
    }
};


