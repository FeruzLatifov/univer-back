<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EDepartment;
use App\Models\ESubject;
use App\Models\EGroup;

/**
 * Translations Seeder
 *
 * Adds Russian and English translations to existing data
 * This is an example - you should customize based on your actual data
 */
class TranslationsSeeder extends Seeder
{
    /**
     * Common department translations
     */
    protected array $departmentTranslations = [
        // Fakultetlar
        'Axborot texnologiyalari fakulteti' => [
            'ru' => 'Факультет информационных технологий',
            'en' => 'Faculty of Information Technologies',
        ],
        'Iqtisodiyot fakulteti' => [
            'ru' => 'Экономический факультет',
            'en' => 'Faculty of Economics',
        ],
        'Pedagogika fakulteti' => [
            'ru' => 'Педагогический факультет',
            'en' => 'Faculty of Pedagogy',
        ],
        'Tibbiyot fakulteti' => [
            'ru' => 'Медицинский факультет',
            'en' => 'Faculty of Medicine',
        ],

        // Kafedralar
        'Dasturiy injiniring kafedrasi' => [
            'ru' => 'Кафедра программной инженерии',
            'en' => 'Department of Software Engineering',
        ],
        'Axborot tizimlari kafedrasi' => [
            'ru' => 'Кафедра информационных систем',
            'en' => 'Department of Information Systems',
        ],
        'Kompyuter injiniringi kafedrasi' => [
            'ru' => 'Кафедра компьютерной инженерии',
            'en' => 'Department of Computer Engineering',
        ],
        'Iqtisodiyot nazariyasi kafedrasi' => [
            'ru' => 'Кафедра экономической теории',
            'en' => 'Department of Economic Theory',
        ],
        'Moliya va kredit kafedrasi' => [
            'ru' => 'Кафедра финансов и кредита',
            'en' => 'Department of Finance and Credit',
        ],
    ];

    /**
     * Common subject translations
     */
    protected array $subjectTranslations = [
        'Matematika' => [
            'ru' => 'Математика',
            'en' => 'Mathematics',
        ],
        'Fizika' => [
            'ru' => 'Физика',
            'en' => 'Physics',
        ],
        'Informatika' => [
            'ru' => 'Информатика',
            'en' => 'Computer Science',
        ],
        'Dasturlash' => [
            'ru' => 'Программирование',
            'en' => 'Programming',
        ],
        'Ma\'lumotlar bazasi' => [
            'ru' => 'Базы данных',
            'en' => 'Databases',
        ],
        'Veb-dasturlash' => [
            'ru' => 'Веб-программирование',
            'en' => 'Web Programming',
        ],
        'Algoritmlar va ma\'lumotlar tuzilmalari' => [
            'ru' => 'Алгоритмы и структуры данных',
            'en' => 'Algorithms and Data Structures',
        ],
        'Iqtisodiyot nazariyasi' => [
            'ru' => 'Экономическая теория',
            'en' => 'Economic Theory',
        ],
        'Moliyaviy hisobot' => [
            'ru' => 'Финансовая отчетность',
            'en' => 'Financial Reporting',
        ],
        'Ingliz tili' => [
            'ru' => 'Английский язык',
            'en' => 'English Language',
        ],
        'O\'zbek tili' => [
            'ru' => 'Узбекский язык',
            'en' => 'Uzbek Language',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌍 Adding translations to departments...');
        $this->addDepartmentTranslations();

        $this->command->info('🌍 Adding translations to subjects...');
        $this->addSubjectTranslations();

        $this->command->info('🌍 Adding translations to groups...');
        $this->addGroupTranslations();

        $this->command->info('✅ Translations added successfully!');
    }

    /**
     * Add translations to departments
     */
    protected function addDepartmentTranslations(): void
    {
        $departments = EDepartment::all();
        $count = 0;

        foreach ($departments as $department) {
            $name = $department->getOriginal('name');

            // Check if we have predefined translations
            if (isset($this->departmentTranslations[$name])) {
                $translations = $this->departmentTranslations[$name];

                $department->setTranslation('name', 'ru', $translations['ru']);
                $department->setTranslation('name', 'en', $translations['en']);
                $department->save();

                $count++;
                $this->command->line("  ✓ {$name}");
            } else {
                // Auto-translate using simple rules (you can enhance this)
                $autoTranslations = $this->autoTranslate($name);

                $department->setTranslation('name', 'ru', $autoTranslations['ru']);
                $department->setTranslation('name', 'en', $autoTranslations['en']);
                $department->save();

                $this->command->line("  ~ {$name} (auto)");
            }
        }

        $this->command->info("  📊 Total: {$count} departments translated");
    }

    /**
     * Add translations to subjects
     */
    protected function addSubjectTranslations(): void
    {
        $subjects = ESubject::all();
        $count = 0;

        foreach ($subjects as $subject) {
            $name = $subject->getOriginal('name');

            if (isset($this->subjectTranslations[$name])) {
                $translations = $this->subjectTranslations[$name];

                $subject->setTranslation('name', 'ru', $translations['ru']);
                $subject->setTranslation('name', 'en', $translations['en']);
                $subject->save();

                $count++;
                $this->command->line("  ✓ {$name}");
            } else {
                $autoTranslations = $this->autoTranslate($name);

                $subject->setTranslation('name', 'ru', $autoTranslations['ru']);
                $subject->setTranslation('name', 'en', $autoTranslations['en']);
                $subject->save();

                $this->command->line("  ~ {$name} (auto)");
            }
        }

        $this->command->info("  📊 Total: {$count} subjects translated");
    }

    /**
     * Add translations to groups (auto-translate only)
     */
    protected function addGroupTranslations(): void
    {
        $groups = EGroup::all();

        foreach ($groups as $group) {
            $name = $group->getOriginal('name');

            // Groups usually have codes like "IT-101", so we keep them as is
            $group->setTranslation('name', 'ru', $name);
            $group->setTranslation('name', 'en', $name);
            $group->save();
        }

        $this->command->info("  📊 Total: {$groups->count()} groups processed");
    }

    /**
     * Simple auto-translation (placeholder - enhance with real translation service)
     */
    protected function autoTranslate(string $text): array
    {
        // For now, just return the same text
        // In production, you would use a translation API (Google Translate, DeepL, etc.)
        return [
            'ru' => $text . ' (требуется перевод)',
            'en' => $text . ' (translation needed)',
        ];
    }
}
