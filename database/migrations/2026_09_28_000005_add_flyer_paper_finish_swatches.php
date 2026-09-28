<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const SWATCHES = [
        'matte_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/matte-lamination.png',
        'gloss_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/gloss-lamination.png',
        'gloss_varnish' => '/images/product-options/flyers-and-brochures/paper-finishes/gloss-varnish.png',
        'soft_touch_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/soft-touch-lamination.png',
    ];

    public function up(): void
    {
        $this->updatePaperFinishSwatches(false);
    }

    public function down(): void
    {
        $this->updatePaperFinishSwatches(true);
    }

    private function updatePaperFinishSwatches(bool $remove): void
    {
        Product::query()
            ->whereIn('slug', FlyersAndBrochuresProductCatalog::slugs())
            ->get()
            ->each(function (Product $product) use ($remove): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $options = is_array($config['options'] ?? null) ? $config['options'] : [];
                $paperFinish = is_array($options['paper_finish'] ?? null)
                    ? $options['paper_finish']
                    : null;
                $values = is_array($paperFinish['values'] ?? null)
                    ? $paperFinish['values']
                    : [];
                $changed = false;

                foreach ($values as $index => $value) {
                    if (! is_array($value)) {
                        continue;
                    }

                    $code = (string) ($value['code'] ?? '');

                    if (! isset(self::SWATCHES[$code])) {
                        continue;
                    }

                    if ($remove) {
                        if (array_key_exists('swatch_image', $value)) {
                            unset($values[$index]['swatch_image']);
                            $changed = true;
                        }

                        continue;
                    }

                    if (($value['swatch_image'] ?? null) !== self::SWATCHES[$code]) {
                        $values[$index]['swatch_image'] = self::SWATCHES[$code];
                        $changed = true;
                    }
                }

                if (! $changed) {
                    return;
                }

                $options['paper_finish']['values'] = array_values($values);
                $config['options'] = $options;
                $product->forceFill(['product_config' => $config])->saveQuietly();
            });
    }
};
