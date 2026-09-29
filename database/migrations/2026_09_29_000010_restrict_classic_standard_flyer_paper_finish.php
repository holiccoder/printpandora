<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-standard-flyers-and-brochures';

    public function up(): void
    {
        $product = Product::query()
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $config = is_array($product->product_config) ? $product->product_config : [];
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $options['paper_finish'] = [
            'label' => 'Paper Finish',
            'type' => 'select',
            'required' => true,
            'default' => 'gloss_varnish',
            'values' => [[
                'code' => 'gloss_varnish',
                'label' => 'Gloss Varnish',
                'description' => 'A clear glossy coating that enhances color.',
                'swatch_image' => '/images/product-options/flyers-and-brochures/paper-finishes/gloss-varnish.png',
            ]],
        ];
        $config['options'] = $options;

        $product->forceFill(['product_config' => $config])->saveQuietly();
    }

    public function down(): void
    {
        // This is a one-way catalog update. The seeder restores the current
        // canonical configuration if the catalog is rebuilt.
    }
};
