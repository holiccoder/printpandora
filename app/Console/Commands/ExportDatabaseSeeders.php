<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportDatabaseSeeders extends Command
{
    protected $signature = 'db:export-seeders
        {--tables= : Comma-separated list of tables to export (default: all non-internal tables)}
        {--except= : Additional comma-separated tables to exclude}';

    protected $description = 'Export current database table data to JSON snapshot files in database/seeders/data for LiveDataSeeder';

    /**
     * Framework-internal or ephemeral tables that are excluded by default.
     */
    protected array $defaultExcluded = [
        'migrations',
        'sqlite_sequence',
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function handle(): int
    {
        $dataDir = database_path('seeders/data');

        if (! is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $tables = $this->tablesToExport();

        if (empty($tables)) {
            $this->warn('No tables to export.');

            return self::FAILURE;
        }

        $summary = [];

        foreach ($tables as $table) {
            $query = DB::table($table);

            // Stable row order keeps the JSON files diff-friendly.
            $columns = array_column(Schema::getColumns($table), 'name');
            if (in_array('id', $columns, true)) {
                $query->orderBy('id');
            }

            $rows = $query->get()->map(fn ($row) => (array) $row)->all();

            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                $this->error("{$table}: json_encode failed (".json_last_error_msg().') — skipped');
                continue;
            }

            file_put_contents($dataDir.DIRECTORY_SEPARATOR.$table.'.json', $json.PHP_EOL);
            $summary[] = [$table, count($rows)];
        }

        $this->info('Snapshot written to database/seeders/data:');
        $this->table(['Table', 'Rows'], $summary);
        $this->info('Re-run this command any time your data changes.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function tablesToExport(): array
    {
        $all = array_column(Schema::getTables(), 'name');

        if ($only = $this->option('tables')) {
            $all = array_intersect($all, array_map('trim', explode(',', $only)));
        }

        $excluded = $this->defaultExcluded;

        if ($extra = $this->option('except')) {
            $excluded = array_merge($excluded, array_map('trim', explode(',', $extra)));
        }

        return array_values(array_diff($all, $excluded));
    }
}
