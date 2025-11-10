<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * SAFETY: Configures test database to prevent data loss on production database.
     * - If USE_TEST_DATABASE=true (set in phpunit.xml), uses DB_DATABASE_TEST from .env
     * - This ensures RefreshDatabase only runs on test database (test_401)
     * - Production database (hemis_401) is NEVER touched by tests
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // SAFETY: Use test database if configured in phpunit.xml
        if (env('USE_TEST_DATABASE', false)) {
            $testDatabase = env('DB_DATABASE_TEST');

            if (!$testDatabase) {
                throw new \RuntimeException(
                    'DB_DATABASE_TEST is not configured in .env file! ' .
                    'Add DB_DATABASE_TEST=test_401 to your .env file.'
                );
            }

            // Override database configuration to use test database
            config(['database.connections.pgsql.database' => $testDatabase]);
        }

        return $app;
    }
}
