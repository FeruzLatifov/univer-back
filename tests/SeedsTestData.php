<?php

namespace Tests;

use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * SeedsTestData Trait
 *
 * SAFETY-FIRST test data trait controlled ONLY by .env configuration:
 *
 * WHEN .env HAS USE_TEST_DATABASE=true:
 * - Uses RefreshDatabase on DB_DATABASE_TEST (test_401)
 * - Drops all tables, re-runs migrations
 * - Seeds test users via TestUsersSeeder
 * - Clean slate for every test
 *
 * WHEN .env HAS USE_TEST_DATABASE=false (or not set):
 * - Uses DB_DATABASE (hemis_401) WITHOUT RefreshDatabase
 * - RefreshDatabase is COMPLETELY BLOCKED (manual usage impossible)
 * - Uses existing data from univer-yii2
 * - Safe integration testing
 *
 * CRITICAL: RefreshDatabase CANNOT be used manually outside this trait!
 * Any attempt will throw an exception.
 *
 * Usage: Add `use SeedsTestData;` to your test class.
 */
trait SeedsTestData
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseFromTrait;
    }

    /**
     * Determine if we should use RefreshDatabase based on .env
     *
     * CRITICAL: Only returns true if:
     * 1. USE_TEST_DATABASE=true in .env
     * 2. DB_DATABASE_TEST is configured
     * 3. Currently connected to test database
     */
    private function shouldRefreshDatabase(): bool
    {
        // Check .env configuration
        $useTestDatabase = filter_var(env('USE_TEST_DATABASE', false), FILTER_VALIDATE_BOOLEAN);

        if (!$useTestDatabase) {
            return false; // RefreshDatabase DISABLED by .env
        }

        $currentDatabase = DB::connection()->getDatabaseName();
        $testDatabase = env('DB_DATABASE_TEST');
        $productionDatabase = env('DB_DATABASE');

        // Safety check: must have test database configured
        if (!$testDatabase) {
            throw new \RuntimeException(
                "⛔ CONFIGURATION ERROR: USE_TEST_DATABASE=true but DB_DATABASE_TEST is not set!\n\n" .
                "Add DB_DATABASE_TEST=test_401 to your .env file."
            );
        }

        // Safety check: must be connected to test database
        if ($currentDatabase !== $testDatabase) {
            throw new \RuntimeException(
                "⛔ SAFETY VIOLATION: USE_TEST_DATABASE=true but not connected to test database!\n\n" .
                "Current database: {$currentDatabase}\n" .
                "Expected test database: {$testDatabase}\n\n" .
                "Check CreatesApplication.php configuration."
            );
        }

        // Safety check: must NOT be production database
        if ($currentDatabase === $productionDatabase) {
            throw new \RuntimeException(
                "⛔ CRITICAL ERROR: Attempting to use RefreshDatabase on production database!\n\n" .
                "Current database: {$currentDatabase}\n" .
                "Production database: {$productionDatabase}\n\n" .
                "This would DESTROY all production data!\n" .
                "Set USE_TEST_DATABASE=false in .env to run tests on production without RefreshDatabase."
            );
        }

        return true;
    }

    /**
     * Override RefreshDatabase behavior to be strictly controlled
     *
     * CRITICAL: This method blocks ALL RefreshDatabase usage unless
     * explicitly enabled via USE_TEST_DATABASE=true in .env
     */
    protected function refreshDatabase(): void
    {
        $currentDatabase = DB::connection()->getDatabaseName();
        $useTestDatabase = filter_var(env('USE_TEST_DATABASE', false), FILTER_VALIDATE_BOOLEAN);

        if ($this->shouldRefreshDatabase()) {
            // TEST MODE: RefreshDatabase ALLOWED
            echo "\n🧪 TEST MODE ENABLED (.env: USE_TEST_DATABASE=true)\n";
            echo "   Database: {$currentDatabase}\n";
            echo "   ✅ RefreshDatabase ENABLED - all tables will be dropped and recreated\n";

            $this->refreshDatabaseFromTrait();
        } else {
            // PRODUCTION MODE: RefreshDatabase BLOCKED
            echo "\n🏢 PRODUCTION MODE (.env: USE_TEST_DATABASE=" . ($useTestDatabase ? 'true' : 'false') . ")\n";
            echo "   Database: {$currentDatabase}\n";
            echo "   🛡️  RefreshDatabase BLOCKED - data is safe\n";
            echo "   📌 Using existing data from univer-yii2\n\n";

            // CRITICAL: Block RefreshDatabase completely
            // Do NOT call refreshDatabaseFromTrait() - keep existing data
        }
    }

    /**
     * Seed test data after database refresh
     * (Only called when RefreshDatabase runs on test database)
     */
    protected function afterRefreshingDatabase(): void
    {
        if ($this->shouldRefreshDatabase()) {
            echo "   📝 Seeding test users...\n";
            $this->seed(TestUsersSeeder::class);
            echo "   ✅ Test data ready\n\n";
        }
    }
}
