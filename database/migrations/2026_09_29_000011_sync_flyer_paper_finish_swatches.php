<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var list<array{code: string, label: string, description: string, swatch_image: string}>
     */
    private const PAPER_FINISH_VALUES = [
        [
            'code' => 'matte_lamination',
            'label' => 'Matte',
            'description' => 'A low-sheen protective matte film.',
            'swatch_image' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸亚膜.png',
        ],
        [
            'code' => 'gloss_lamination',
            'label' => 'Gloss',
            'description' => 'A bright, reflective protective film.',
            'swatch_image' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸光膜.png',
        ],
        [
            'code' => 'gloss_varnish',
            'label' => 'Gloss Varnish',
            'description' => 'A clear glossy coating that enhances color.',
            'swatch_image' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸光油.png',
        ],
        [
            'code' => 'soft_touch_lamination',
            'label' => 'Soft-Touch',
            'description' => 'A smooth, velvety protective film.',
            'swatch_image' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸触感膜.png',
        ],
    ];

    public function up(): void
    {
        Product::query()
            ->whereIn('slug', FlyersAndBrochuresProductCatalog::slugs())
            ->get()
            ->each(function (Product $product): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $options = is_array($config['options'] ?? null) ? $config['options'] : [];
                $paperFinish = [
                    'label' => 'Paper Finish',
                    'type' => 'select',
                    'required' => true,
                    'default' => 'matte_lamination',
                    'values' => self::PAPER_FINISH_VALUES,
                ];
                $normalizedOptions = [];
                $paperFinishInserted = false;

                foreach ($options as $key => $value) {
                    if ($key === 'paper_finish') {
                        continue;
                    }

                    if ($key === 'folding' && ! $paperFinishInserted) {
                        $normalizedOptions['paper_finish'] = $paperFinish;
                        $paperFinishInserted = true;
                    }

                    $normalizedOptions[$key] = $value;
                }

                if (! $paperFinishInserted) {
                    $normalizedOptions['paper_finish'] = $paperFinish;
                }

                if (($config['options'] ?? null) === $normalizedOptions) {
                    return;
                }

                $config['options'] = $normalizedOptions;
                $product->forceFill(['product_config' => $config])->saveQuietly();
            });
    }

    public function down(): void
    {
        // The supplied flyer finish artwork is intentionally retained.
    }
};
