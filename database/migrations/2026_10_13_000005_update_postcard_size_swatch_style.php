<?php

use App\Models\Product;
use App\Support\PostcardProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sizeOptions = PostcardProductCatalog::sizeOptions();

        DB::transaction(function () use ($sizeOptions): void {
            foreach (Product::query()->whereIn('slug', PostcardProductCatalog::slugs())->get() as $product) {
                $config = $product->product_config;

                if (! is_array($config) || $config === []) {
                    continue;
                }

                $config['options'] = is_array($config['options'] ?? null)
                    ? $config['options']
                    : [];
                $config['options']['sizes'] = $sizeOptions;

                $product->forceFill(['product_config' => $config])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Keep the postcard size swatches consistent with the storefront.
    }
};
