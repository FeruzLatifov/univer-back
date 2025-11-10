<?php

namespace App\Console\Commands;

use App\Models\System\SystemMessage;
use App\Models\System\SystemMessageTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Import translations from CSV file
 * 
 * Usage:
 *   php artisan translation:import
 *   php artisan translation:import --file=custom.csv
 *   php artisan translation:import --force
 * 
 * CSV Format:
 *   category,message,uz-UZ,oz-UZ,ru-RU,en-US,kk-UZ,tg-TG,kz-KZ,tm-TM,kg-KG
 *   app,Dashboard,Boshqaruv paneli,Бошқарув панели,Панель управления,Dashboard
 * 
 * Important:
 *   - Only updates 'translation' column (base translations)
 *   - Does NOT touch 'custom_translation' column
 *   - University customizations are preserved!
 */
class ImportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'translation:import 
                            {--file= : CSV file path (default: common/data/translations.csv)}
                            {--force : Force update even if translation exists}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     */
    protected $description = 'Import base translations from CSV file';

    /**
     * Supported languages from CSV
     */
    protected $languages = [
        'uz-UZ',
        'oz-UZ',
        'ru-RU',
        'en-US',
        'kk-UZ',
        'tg-TG',
        'kz-KZ',
        'tm-TM',
        'kg-KG',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('common/data/translations.csv');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Check file exists
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return 1;
        }

        $this->info("📂 Reading file: {$filePath}");
        $this->newLine();

        // Read CSV
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("❌ Cannot open file: {$filePath}");
            return 1;
        }

        // Read header
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error("❌ Invalid CSV format: no header row");
            fclose($handle);
            return 1;
        }

        // Parse header
        $columnMap = array_flip($headers);
        
        // Validate required columns
        if (!isset($columnMap['category']) || !isset($columnMap['message'])) {
            $this->error("❌ CSV must have 'category' and 'message' columns");
            fclose($handle);
            return 1;
        }

        $this->info("🔍 CSV columns detected: " . implode(', ', $headers));
        $this->newLine();

        // Statistics
        $stats = [
            'total' => 0,
            'created_messages' => 0,
            'updated_translations' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Progress bar
        $totalLines = count(file($filePath)) - 1; // Exclude header
        $bar = $this->output->createProgressBar($totalLines);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        // Process rows
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $stats['total']++;

                if (count($row) != count($headers)) {
                    $stats['errors']++;
                    $bar->setMessage("⚠️  Row {$stats['total']}: Column count mismatch");
                    $bar->advance();
                    continue;
                }

                // Parse row
                $data = [];
                foreach ($columnMap as $name => $index) {
                    $data[$name] = trim($row[$index]);
                }

                // Get category and message
                $category = $data['category'] ?: 'app';
                $message = $data['message'];

                if (empty($message)) {
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                // Find or create message
                $messageModel = SystemMessage::firstOrCreate(
                    [
                        'category' => $category,
                        'message' => $message,
                    ]
                );

                if ($messageModel->wasRecentlyCreated) {
                    $stats['created_messages']++;
                }

                // Import translations for each language
                foreach ($this->languages as $language) {
                    if (!isset($columnMap[$language])) {
                        continue;
                    }

                    $translation = $data[$language] ?? null;

                    if (empty($translation)) {
                        continue;
                    }

                    // Update or create translation
                    if (!$dryRun) {
                        $updated = $this->updateTranslation(
                            $messageModel->id,
                            $language,
                            $translation,
                            $force
                        );

                        if ($updated) {
                            $stats['updated_translations']++;
                        }
                    } else {
                        $stats['updated_translations']++;
                    }
                }

                $bar->setMessage("Processing: {$category}/{$message}");
                $bar->advance();
            }

            if (!$dryRun) {
                DB::commit();
                
                // Clear cache
                Cache::tags(['translations'])->flush();
                
                $bar->setMessage('✅ Committed to database');
            } else {
                DB::rollBack();
                $bar->setMessage('🔍 Dry run completed (no changes)');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine(2);
            $this->error("❌ Import failed: " . $e->getMessage());
            fclose($handle);
            return 1;
        }

        $bar->finish();
        fclose($handle);

        // Summary
        $this->newLine(2);
        $this->info('📊 Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total rows processed', number_format($stats['total'])],
                ['Messages created', number_format($stats['created_messages'])],
                ['Translations updated', number_format($stats['updated_translations'])],
                ['Rows skipped', number_format($stats['skipped'])],
                ['Errors', number_format($stats['errors'])],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 This was a DRY RUN - no changes were made');
            $this->info('Run without --dry-run to actually import');
        } else {
            $this->newLine();
            $this->info('✅ Import completed successfully!');
            $this->info('🗑️  Translation cache cleared');
        }

        return 0;
    }

    /**
     * Update translation (only base translation, custom is preserved)
     */
    protected function updateTranslation(int $messageId, string $language, string $translation, bool $force): bool
    {
        $existing = SystemMessageTranslation::where('id', $messageId)
            ->where('language', $language)
            ->first();

        if ($existing) {
            // Update only if different or force flag
            if ($force || $existing->translation !== $translation) {
                // IMPORTANT: Only update 'translation' column (base)
                // Do NOT touch 'custom_translation' column!
                $existing->update([
                    'translation' => $translation,
                    // custom_translation NOT changed! ✅
                ]);
                return true;
            }
            return false;
        } else {
            // Create new translation
            SystemMessageTranslation::create([
                'id' => $messageId,
                'language' => $language,
                'translation' => $translation,
                'custom_translation' => null, // No custom yet
            ]);
            return true;
        }
    }
}
