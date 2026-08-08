<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use Illuminate\Console\Command;

class ImportProductConfigurations extends Command
{
    protected $signature = 'products:import-configurations
        {--slug=* : Import only the provided product slug(s)}
        {--force : Replace existing product_config values}';

    protected $description = 'Import legacy product option and pricing files into products.product_config';

    public function handle(ProductConfigurationService $configuration): int
    {
        $slugs = array_values(array_filter(array_map('strval', $this->option('slug'))));
        $force = (bool) $this->option('force');
        $imported = 0;
        $skipped = 0;

        Product::query()
            ->with('category')
            ->when($slugs !== [], fn ($query) => $query->whereIn('slug', $slugs))
            ->orderBy('id')
            ->each(function (Product $product) use ($configuration, $force, &$imported, &$skipped): void {
                if (! $force && is_array($product->product_config) && $product->product_config !== []) {
                    $skipped++;

                    return;
                }

                if (! $configuration->hasLegacyConfiguration($product)) {
                    $skipped++;

                    return;
                }

                $product->forceFill([
                    'product_config' => $configuration->canonicalConfig($product),
                ])->save();

                $imported++;
            });

        $this->info("Imported {$imported} product configuration(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
