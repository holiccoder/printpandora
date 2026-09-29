<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const CLASSIC_STANDARD_SLUG = 'classic-standard-flyers-and-brochures';

    /**
     * @var list<string>
     */
    private const PRODUCTS_WITHOUT_PAPER_FINISH = [
        'quality-flyers-and-brochures',
        'special-flyers-and-brochures',
        'super-flyers-and-brochures',
    ];

    public function up(): void
    {
        $classicStandard = FlyersAndBrochuresProductCatalog::definition(self::CLASSIC_STANDARD_SLUG);
        $classicStandardPaperFinish = data_get($classicStandard, 'product_config.options.paper_finish');

        Product::query()
            ->whereIn('slug', [
                self::CLASSIC_STANDARD_SLUG,
                ...self::PRODUCTS_WITHOUT_PAPER_FINISH,
            ])
            ->get()
            ->each(function (Product $product) use ($classicStandardPaperFinish): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $options = is_array($config['options'] ?? null) ? $config['options'] : [];

                if ($product->slug === self::CLASSIC_STANDARD_SLUG) {
                    if (is_array($classicStandardPaperFinish)) {
                        $options['paper_finish'] = $classicStandardPaperFinish;
                    }
                } else {
                    unset($options['paper_finish']);
                }

                if (($config['options'] ?? null) === $options) {
                    return;
                }

                $config['options'] = $options;
                $product->forceFill(['product_config' => $config])->saveQuietly();
            });
    }

    public function down(): void
    {
        // This is a one-way catalog update. The seeder restores the current
        // canonical configuration if the catalog is rebuilt.
    }
};
