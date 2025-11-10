<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Map Yii2 Path-Based Permissions to Laravel Name-Based
 *
 * Converts:
 * - "archive/academic-record" → "archive-academic-record"
 * - "teacher/attendance" → "teacher-attendance"
 *
 * Updates permission_name column in e_admin_resource
 */
class MapYii2ToLaravelPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Mapping Yii2 paths to Laravel permission names...');

        // Get all resources
        $resources = DB::table('e_admin_resource')
            ->select('id', 'path', 'name')
            ->orderBy('id')
            ->get();

        $this->command->info("Found {$resources->count()} resources");

        $updated = 0;

        foreach ($resources as $resource) {
            // Convert: "archive/academic-record" → "archive-academic-record"
            $permissionName = str_replace('/', '-', $resource->path);

            DB::table('e_admin_resource')
                ->where('id', $resource->id)
                ->update([
                    'permission_name' => $permissionName,
                    'updated_at' => now(),
                ]);

            $updated++;

            // Progress
            if ($updated % 50 === 0) {
                $this->command->info("Processed {$updated}...");
            }
        }

        $this->command->info("✅ Mapped {$updated} permissions!");

        // Show samples
        $this->showSamples();

        // Verify
        $nullCount = DB::table('e_admin_resource')
            ->whereNull('permission_name')
            ->count();

        if ($nullCount > 0) {
            $this->command->warn("⚠️ {$nullCount} resources still have NULL permission_name");
        } else {
            $this->command->info("✅ All resources mapped successfully!");
        }
    }

    /**
     * Show sample mappings
     */
    private function showSamples(): void
    {
        $this->command->info("\n📊 Sample Mappings:");

        $samples = DB::table('e_admin_resource')
            ->select('path', 'permission_name', 'name')
            ->limit(10)
            ->get();

        $this->command->table(
            ['Yii2 Path', 'Laravel Permission', 'Name'],
            $samples->map(fn($s) => [
                $s->path,
                $s->permission_name,
                $s->name,
            ])->toArray()
        );
    }
}
