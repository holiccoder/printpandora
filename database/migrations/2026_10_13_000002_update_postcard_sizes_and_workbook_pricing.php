<?php

use App\Models\Product;
use App\Services\ProductConfigurationService;
use App\Support\PostcardProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $configuration = app(ProductConfigurationService::class);

            foreach (Product::query()->whereIn('slug', PostcardProductCatalog::slugs())->get() as $product) {
                $config = $product->product_config;

                if (! is_array($config) || $config === []) {
                    $config = $configuration->canonicalConfig($product);
                }

                $product->forceFill([
                    'product_config' => PostcardProductCatalog::synchronizeConfig($config, (string) $product->slug),
                ])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Keep the corrected postcard dimensions and workbook pricing.
    }
};
