<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PRODUCTS = [
        'quality-flyers-and-brochures',
        'special-flyers-and-brochures',
        'super-flyers-and-brochures',
    ];

    public function up(): void
    {
        foreach (self::PRODUCTS as $slug) {
            $definition = FlyersAndBrochuresProductCatalog::definition($slug);
            $folding = data_get($definition, 'product_config.options.folding');

            if (! is_array($folding)) {
                continue;
            }

            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                continue;
            }

            $config = is_array($product->product_config) ? $product->product_config : [];
            $options = is_array($config['options'] ?? null) ? $config['options'] : [];

            if (($options['folding'] ?? null) === $folding) {
                continue;
            }

            $options['folding'] = $folding;
            $config['options'] = $options;
            $product->forceFill(['product_config' => $config])->saveQuietly();
        }
    }

    public function down(): void
    {
        foreach (self::PRODUCTS as $slug) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                continue;
            }

            $config = is_array($product->product_config) ? $product->product_config : [];
            $options = is_array($config['options'] ?? null) ? $config['options'] : [];
            $folding = is_array($options['folding'] ?? null) ? $options['folding'] : [];
            $values = is_array($folding['values'] ?? null) ? $folding['values'] : [];
            $halfFold = array_values(array_filter(
                $values,
                static fn (mixed $value): bool => is_array($value) && ($value['code'] ?? null) === 'half_fold',
            ));

            if ($halfFold === []) {
                continue;
            }

            $folding['default'] = 'half_fold';
            $folding['values'] = $halfFold;
            $options['folding'] = $folding;
            $config['options'] = $options;
            $product->forceFill(['product_config' => $config])->saveQuietly();
        }
    }
};
