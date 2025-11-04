<?php

namespace App\Console\Commands;

use App\Services\ApiDocumentationService;
use Illuminate\Console\Command;

/**
 * Generate role-specific API documentation from master spec
 *
 * Usage:
 *   php artisan docs:generate teacher
 *   php artisan docs:generate --all
 */
class GenerateApiDocs extends Command
{
    protected $signature = 'docs:generate {role?} {--all}';

    protected $description = 'Generate role-specific API documentation from master spec';

    private ApiDocumentationService $docService;

    public function __construct(ApiDocumentationService $docService)
    {
        parent::__construct();
        $this->docService = $docService;
    }

    public function handle(): int
    {
        $this->info('🚀 Generating API Documentation...');
        $this->newLine();

        if ($this->option('all')) {
            $this->generateAll();
        } else {
            $role = $this->argument('role');

            if (!$role) {
                $role = $this->choice(
                    'Select role to generate:',
                    ['teacher', 'student', 'admin', 'integration'],
                    0
                );
            }

            $this->generateRole($role);
        }

        $this->newLine();
        $this->info('✅ Done!');

        return self::SUCCESS;
    }

    private function generateAll(): void
    {
        $roles = ['teacher', 'student', 'admin', 'integration'];

        foreach ($roles as $role) {
            $this->generateRole($role);
        }
    }

    private function generateRole(string $role): void
    {
        $this->line("📝 Generating {$role} documentation...");

        try {
            // Generate filtered spec
            $filteredSpec = $this->docService->generateRoleView($role);

            // Count endpoints
            $endpointCount = $this->countEndpoints($filteredSpec);

            // Export to YAML
            $filename = "{$role}-api.yaml";
            $this->docService->exportToYaml($filteredSpec, $filename);

            $this->info("   ✓ {$role}-api.yaml generated ({$endpointCount} endpoints)");
        } catch (\Exception $e) {
            $this->error("   ✗ Error generating {$role}: " . $e->getMessage());
        }
    }

    private function countEndpoints(array $spec): int
    {
        $count = 0;

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
