<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()
            ->whereIn('slug', FlyersAndBrochuresProductCatalog::slugs())
            ->get()
            ->each(function (Product $product): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $options = is_array($config['options'] ?? null) ? $config['options'] : [];
                $paperFinish = is_array($options['paper_finish'] ?? null)
                    ? $options['paper_finish']
                    : null;
                $values = is_array($paperFinish['values'] ?? null)
                    ? $paperFinish['values']
                    : [];

                $firstValue = $values[0] ?? null;

                if (
                    count($values) !== 1
                    || ! is_array($firstValue)
                    || ($firstValue['code'] ?? null) !== 'none'
                ) {
                    return;
                }

                unset($options['paper_finish']);
                $config['options'] = $options;

                $product->forceFill(['product_config' => $config])->saveQuietly();
            });
    }

    public function down(): void
    {
        // This is a one-way catalog cleanup. The seeder restores the current
        // canonical configuration if the catalog is rebuilt.
    }
};
