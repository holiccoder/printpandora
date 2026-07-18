<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveDataSeeder extends Seeder
{
    /**
     * Re-seed the database from the JSON snapshot in database/seeders/data.
     *
     * Generate or update the snapshot with: php artisan db:export-seeders
     */
    public function run(): void
    {
        $files = glob(database_path('seeders/data/*.json'));

        if (empty($files)) {
            $this->command?->warn('No snapshot files found in database/seeders/data. Run php artisan db:export-seeders first.');

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($files as $file) {
                $table = basename($file, '.json');
                $rows = json_decode(file_get_contents($file), true);

                if (! is_array($rows)) {
                    $this->command?->error("{$table}: invalid JSON — skipped");
                    continue;
                }

                DB::table($table)->truncate();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                $this->command?->info("{$table}: ".count($rows).' rows');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
