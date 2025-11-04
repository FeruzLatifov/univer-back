<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncLanguageTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:sync {--force : Force sync even if files exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync menu translations from database to language files';

    /**
     * Default active languages (always enabled)
     */
    protected array $defaultLanguages = [
        'uz-UZ' => 'uz',
        'oz-UZ' => 'oz',
        'ru-RU' => 'ru',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting language translation sync...');

        // 1. Get active languages
        $activeLanguages = $this->getActiveLanguages();
        $this->info('✅ Found ' . count($activeLanguages) . ' active language(s): ' . implode(', ', array_keys($activeLanguages)));

        // 2. Get menu translations from database
        $this->info('📥 Fetching menu translations from database...');
        $translations = $this->getMenuTranslations($activeLanguages);

        if (empty($translations)) {
            $this->warn('⚠️  No menu translations found in database!');
            return 0;
        }

        $this->info('✅ Found ' . count($translations) . ' translatable message(s)');

        // 3. Generate language files
        $this->info('📝 Generating language files...');
        $generatedCount = 0;

        foreach ($activeLanguages as $fullCode => $shortCode) {
            $count = $this->generateLanguageFile($shortCode, $fullCode, $translations);
            if ($count > 0) {
                $this->info("   ✅ lang/{$shortCode}/menu.php - {$count} translation(s)");
                $generatedCount++;
            }
        }

        // 4. Clear cache
        $this->info('🧹 Clearing translation cache...');
        $this->call('cache:clear');
        $this->call('config:clear');

        $this->info('');
        $this->info("🎉 Successfully synced {$generatedCount} language file(s)!");

        return 0;
    }

    /**
     * Get active languages from system config
     */
    protected function getActiveLanguages(): array
    {
        $languages = $this->defaultLanguages;

        // Get additional languages from system config
        $configs = DB::table('e_system_config')
            ->where('path', 'like', 'system_language_%')
            ->where('value', '1')
            ->distinct()
            ->pluck('path')
            ->toArray();

        foreach ($configs as $path) {
            // Extract language code from path (e.g., 'system_language_en-US' => 'en-US')
            $fullCode = str_replace('system_language_', '', $path);

            // Convert to short code (e.g., 'en-US' => 'en')
            $shortCode = strtolower(explode('-', $fullCode)[0]);

            // Add if not already in defaults
            if (!isset($languages[$fullCode])) {
                $languages[$fullCode] = $shortCode;
            }
        }

        return $languages;
    }

    /**
     * Get menu translations from database
     */
    protected function getMenuTranslations(array $activeLanguages): array
    {
        $fullCodes = array_keys($activeLanguages);

        // Get all messages from 'app' category with their translations
        $results = DB::table('e_system_message as m')
            ->leftJoin('e_system_message_translation as t', 'm.id', '=', 't.id')
            ->where('m.category', 'app')
            ->whereIn('t.language', $fullCodes)
            ->select('m.message', 't.language', 't.translation')
            ->get();

        // Group translations by language and message
        $translations = [];
        foreach ($results as $row) {
            if (empty($row->translation)) {
                continue;
            }

            $shortCode = $activeLanguages[$row->language] ?? null;
            if (!$shortCode) {
                continue;
            }

            if (!isset($translations[$shortCode])) {
                $translations[$shortCode] = [];
            }

            $translations[$shortCode][$row->message] = $row->translation;
        }

        return $translations;
    }

    /**
     * Generate language file for specific language
     */
    protected function generateLanguageFile(string $shortCode, string $fullCode, array $allTranslations): int
    {
        $langDir = base_path("lang/{$shortCode}");
        $filePath = "{$langDir}/menu.php";

        // Create directory if not exists
        if (!File::exists($langDir)) {
            File::makeDirectory($langDir, 0755, true);
        }

        // Check if file exists and --force not provided
        if (File::exists($filePath) && !$this->option('force')) {
            $this->warn("   ⏭️  lang/{$shortCode}/menu.php already exists (use --force to overwrite)");
            return 0;
        }

        // Get translations for this language
        $translations = $allTranslations[$shortCode] ?? [];

        if (empty($translations)) {
            $this->warn("   ⚠️  No translations found for {$shortCode}");
            return 0;
        }

        // Sort translations alphabetically by key
        ksort($translations);

        // Generate PHP array code
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * Menu Translations ({$shortCode})\n";
        $content .= " * \n";
        $content .= " * Auto-generated from database (e_system_message_translation)\n";
        $content .= " * DO NOT EDIT MANUALLY - Use 'php artisan lang:sync' to regenerate\n";
        $content .= " * \n";
        $content .= " * Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";
        $content .= "return [\n";

        foreach ($translations as $key => $value) {
            // Escape single quotes in key and value
            $key = str_replace("'", "\\'", $key);
            $value = str_replace("'", "\\'", $value);

            $content .= "    '{$key}' => '{$value}',\n";
        }

        $content .= "];\n";

        // Write file
        File::put($filePath, $content);

        return count($translations);
    }
}
