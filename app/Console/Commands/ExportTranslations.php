<?php

namespace App\Console\Commands;

use App\Models\System\SystemMessage;
use App\Models\System\SystemMessageTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Export translations to CSV file
 * 
 * Usage:
 *   php artisan translation:export
 *   php artisan translation:export --file=output.csv
 *   php artisan translation:export --include-custom
 *   php artisan translation:export --only-custom
 * 
 * Output formats:
 * 
 * Default (base only):
 *   category,message,uz-UZ,oz-UZ,ru-RU,en-US,...
 *   app,Dashboard,Boshqaruv paneli,Бошқарув панели,Панель управления,Dashboard
 * 
 * With custom (--include-custom):
 *   category,message,uz-UZ,uz-UZ-custom,oz-UZ,oz-UZ-custom,ru-RU,ru-RU-custom,...
 *   app,Dashboard,Boshqaruv paneli,Asosiy sahifa,Бошқарув панели,,Панель управления,
 * 
 * Only custom (--only-custom):
 *   category,message,uz-UZ,oz-UZ,ru-RU,...
 *   app,Dashboard,Asosiy sahifa,,,
 */
class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'translation:export 
                            {--file= : Output CSV file path (default: storage/app/translations.csv)}
                            {--include-custom : Include custom translations as separate columns}
                            {--only-custom : Export only custom translations}
                            {--languages= : Comma-separated language codes (default: all)}';

    /**
     * The console command description.
     */
    protected $description = 'Export translations to CSV file';

    /**
     * Supported languages
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
        $filePath = $this->option('file') ?: storage_path('app/translations.csv');
        $includeCustom = $this->option('include-custom');
        $onlyCustom = $this->option('only-custom');
        $languagesOption = $this->option('languages');

        // Parse languages
        if ($languagesOption) {
            $this->languages = array_map('trim', explode(',', $languagesOption));
        }

        $this->info("📤 Exporting translations to: {$filePath}");
        $this->newLine();

        // Open file
        $handle = fopen($filePath, 'w');
        if (!$handle) {
            $this->error("❌ Cannot create file: {$filePath}");
            return 1;
        }

        // Write header
        $header = ['category', 'message'];
        
        if ($includeCustom) {
            // With custom: uz-UZ, uz-UZ-custom, oz-UZ, oz-UZ-custom, ...
            foreach ($this->languages as $lang) {
                $header[] = $lang;
                $header[] = $lang . '-custom';
            }
        } else {
            // Normal: uz-UZ, oz-UZ, ru-RU, ...
            $header = array_merge($header, $this->languages);
        }

        fputcsv($handle, $header);

        // Query
        $query = SystemMessage::with('translations');

        if ($onlyCustom) {
            // Only messages that have at least one custom translation
            $query->whereHas('translations', function ($q) {
                $q->whereNotNull('custom_translation');
            });
        }

        $messages = $query->orderBy('category')->orderBy('message')->get();

        if ($messages->isEmpty()) {
            $this->warn('⚠️  No translations found to export');
            fclose($handle);
            return 0;
        }

        // Progress bar
        $bar = $this->output->createProgressBar($messages->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Exporting...');
        $bar->start();

        $exported = 0;

        foreach ($messages as $message) {
            $row = [
                $message->category,
                $message->message,
            ];

            // Index translations by language
            $translationsMap = [];
            foreach ($message->translations as $trans) {
                $translationsMap[$trans->language] = $trans;
            }

            // Add translation columns
            if ($includeCustom) {
                // Include both base and custom
                foreach ($this->languages as $lang) {
                    $trans = $translationsMap[$lang] ?? null;
                    
                    // Base translation
                    $row[] = $trans ? ($trans->translation ?? '') : '';
                    
                    // Custom translation
                    $row[] = $trans ? ($trans->custom_translation ?? '') : '';
                }
            } elseif ($onlyCustom) {
                // Only custom translations
                foreach ($this->languages as $lang) {
                    $trans = $translationsMap[$lang] ?? null;
                    $row[] = $trans ? ($trans->custom_translation ?? '') : '';
                }
            } else {
                // Default: base translations only
                foreach ($this->languages as $lang) {
                    $trans = $translationsMap[$lang] ?? null;
                    $row[] = $trans ? ($trans->translation ?? '') : '';
                }
            }

            fputcsv($handle, $row);
            $exported++;

            $bar->setMessage("Exported: {$message->category}/{$message->message}");
            $bar->advance();
        }

        $bar->finish();
        fclose($handle);

        // Summary
        $this->newLine(2);
        $this->info("✅ Export completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['File', $filePath],
                ['Messages exported', number_format($exported)],
                ['Languages', implode(', ', $this->languages)],
                ['Include custom', $includeCustom ? 'Yes' : 'No'],
                ['Only custom', $onlyCustom ? 'Yes' : 'No'],
            ]
        );

        return 0;
    }
}
