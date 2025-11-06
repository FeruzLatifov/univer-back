<?php

namespace App\Console\Commands;

use App\Models\SystemLogin;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSystemLoginCommand extends Command
{
    protected $signature = 'audit:import-system-login
        {--connection= : Legacy database connection name}
        {--chunk=1000 : Chunk size for processing}
        {--truncate : Truncate destination table before import}
        {--dry-run : Run import without writing to database}';

    protected $description = 'Import legacy e_system_login records into the new SystemLogin audit table';

    public function handle(): int
    {
        $connectionName = $this->option('connection')
            ?? config('database.legacy_connection')
            ?? env('LEGACY_DB_CONNECTION');

        if (!$connectionName) {
            $this->error('No legacy database connection configured. Use --connection option or set LEGACY_DB_CONNECTION.');
            return self::FAILURE;
        }

        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $legacyDb = DB::connection($connectionName);

        if (!$legacyDb->getSchemaBuilder()->hasTable('e_system_login')) {
            $this->error("Table e_system_login not found on connection {$connectionName}.");
            return self::FAILURE;
        }

        if ($this->option('truncate') && !$dryRun) {
            $this->warn('Truncating system login table...');
            SystemLogin::query()->truncate();
        }

        $total = (int) $legacyDb->table('e_system_login')->count();
        $processed = 0;

        $this->info("Starting import from connection [{$connectionName}] ({$total} rows)...");

        $legacyDb->table('e_system_login')
            ->orderBy('created_at')
            ->chunk($chunkSize, function ($rows) use (&$processed, $dryRun) {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'login' => $row->login ?? null,
                        'status' => $row->status ?? SystemLogin::STATUS_FAILED,
                        'type' => $row->type ?? SystemLogin::TYPE_LOGIN,
                        'ip' => $row->ip ?? null,
                        'query' => $row->query ?? null,
                        'user' => $row->user ?? null,
                        'created_at' => $this->carbonize($row->created_at),
                    ];
                }

                if (!$dryRun && !empty($payload)) {
                    SystemLogin::query()->upsert($payload, ['login', 'created_at'], ['status', 'type', 'ip', 'query', 'user']);
                }

                $processed += count($rows);
                $this->output->write('.');
            });

        $this->newLine();
        $this->info(sprintf(
            'Import completed. %s rows %s.',
            number_format($processed),
            $dryRun ? 'scanned (dry-run)' : 'inserted/updated'
        ));

        return self::SUCCESS;
    }

    private function carbonize($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse($value);
    }
}

