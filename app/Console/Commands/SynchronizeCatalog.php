<?php

namespace App\Console\Commands;

use App\Services\CatalogSynchronizationService;
use Illuminate\Console\Command;
use Throwable;

class SynchronizeCatalog extends Command
{
    protected $signature = 'catalog:sync
        {--source= : Snapshot directory containing product_categories.json and products.json}
        {--dry-run : Validate the snapshot and preview changes without writing to the database}
        {--prune : Delete products and categories that are absent from the snapshot}
        {--force : Allow a destructive prune without interactive confirmation}';

    protected $description = 'Safely synchronize product categories and products from JSON snapshots by slug';

    public function handle(CatalogSynchronizationService $synchronizer): int
    {
        $source = $this->resolveSourceDirectory((string) $this->option('source'));
        $dryRun = (bool) $this->option('dry-run');
        $prune = (bool) $this->option('prune');
        $force = (bool) $this->option('force');

        if ($prune && ! $dryRun && ! $force) {
            if (app()->isProduction() || ! $this->input->isInteractive()) {
                $this->components->error('A real --prune requires --force in production or non-interactive mode.');

                return self::FAILURE;
            }

            if (! $this->confirm('Delete every remote product and category that is absent from the snapshot?', false)) {
                $this->components->warn('Catalog synchronization cancelled; no database changes were made.');

                return self::SUCCESS;
            }
        }

        try {
            $summary = $synchronizer->synchronize(
                sourceDirectory: $source,
                dryRun: $dryRun,
                prune: $prune,
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($exception->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->components->info($dryRun
            ? 'Catalog synchronization preview'
            : 'Catalog synchronization completed');
        $this->line("Source: {$source}");
        $this->table(
            ['Resource', 'Create', 'Update', 'Unchanged', 'Delete'],
            [
                [
                    'Product categories',
                    $summary['categories']['create'],
                    $summary['categories']['update'],
                    $summary['categories']['unchanged'],
                    $summary['categories']['delete'],
                ],
                [
                    'Products',
                    $summary['products']['create'],
                    $summary['products']['update'],
                    $summary['products']['unchanged'],
                    $summary['products']['delete'],
                ],
            ],
        );

        if ($dryRun) {
            $this->components->warn('Dry run only; no database changes were made.');
        }

        $this->components->warn('Uploaded image files are not synchronized by this command.');

        return self::SUCCESS;
    }

    private function resolveSourceDirectory(string $source): string
    {
        $source = trim($source);

        if ($source === '') {
            return database_path('seeders/data');
        }

        $isAbsolute = (bool) preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/', $source);

        return $isAbsolute ? $source : base_path($source);
    }
}
