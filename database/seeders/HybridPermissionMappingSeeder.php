<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Hybrid Permission Mapping Seeder
 *
 * Maps Yii2 path-based permissions to Laravel name-based permissions
 * in the hybrid permission system.
 *
 * Strategy:
 * - Converts Yii2 paths (e.g., "archive/academic-record") to Laravel names (e.g., "archive-academic-record")
 * - Updates permission_name column in e_admin_resource table
 * - Maintains backward compatibility with Yii2 path-based system
 * - Enables gradual migration to Laravel name-based permissions
 *
 * Example mappings:
 * - "archive/academic-record" → "archive-academic-record"
 * - "teacher/attendance-journal" → "teacher-attendance-journal"
 * - "curriculum/curriculum-edit" → "curriculum-curriculum-edit"
 */
class HybridPermissionMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Yii2 → Laravel permission mapping...');

        // Get all resources from e_admin_resource
        $resources = DB::table('e_admin_resource')
            ->select('id', 'path', 'name')
            ->orderBy('id')
            ->get();

        $this->command->info("Found {$resources->count()} resources to map");

        $updated = 0;
        $skipped = 0;

        foreach ($resources as $resource) {
            // Convert Yii2 path to Laravel permission name
            // Example: "archive/academic-record" → "archive-academic-record"
            $permissionName = $this->convertPathToPermissionName($resource->path);

            // Update the resource with the new permission_name
            DB::table('e_admin_resource')
                ->where('id', $resource->id)
                ->update([
                    'permission_name' => $permissionName,
                    'updated_at' => now(),
                ]);

            $updated++;

            // Show progress every 50 records
            if ($updated % 50 === 0) {
                $this->command->info("Processed {$updated} resources...");
            }
        }

        $this->command->info("✅ Permission mapping completed!");
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Resources', $resources->count()],
                ['Updated', $updated],
                ['Skipped', $skipped],
            ]
        );

        // Show sample mappings
        $this->showSampleMappings();
    }

    /**
     * Convert Yii2 path to Laravel permission name
     *
     * @param string $path Yii2 path (e.g., "archive/academic-record")
     * @return string Laravel permission name (e.g., "archive-academic-record")
     */
    private function convertPathToPermissionName(string $path): string
    {
        // Replace "/" with "-" to create Laravel-compatible permission name
        // This maintains the hierarchical structure while being name-based
        return str_replace('/', '-', $path);
    }

    /**
     * Show sample mappings for verification
     */
    private function showSampleMappings(): void
    {
        $this->command->info("\n📊 Sample Mappings:");

        $samples = DB::table('e_admin_resource')
            ->select('path', 'permission_name', 'name')
            ->limit(10)
            ->get();

        $this->command->table(
            ['Yii2 Path', 'Laravel Permission', 'Display Name'],
            $samples->map(function ($sample) {
                return [
                    $sample->path,
                    $sample->permission_name,
                    $sample->name,
                ];
            })->toArray()
        );

        // Verify all records have permission_name
        $withoutPermission = DB::table('e_admin_resource')
            ->whereNull('permission_name')
            ->count();

        if ($withoutPermission > 0) {
            $this->command->warn("⚠️ Warning: {$withoutPermission} resources still have NULL permission_name");
        } else {
            $this->command->info("✅ All resources have permission_name mapped!");
        }
    }
}
