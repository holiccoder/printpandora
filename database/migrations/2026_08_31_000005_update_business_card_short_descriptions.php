<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const PRODUCT_SUBTITLE_FILES = [
        'classic-standard-business-cards' => 'content/product-options/business-cards/classic-standard-business-cards.json',
        'classic-special-business-cards' => 'content/product-options/business-cards/classic-special-business-cards.json',
        'classic-quality-business-cards' => 'content/product-options/business-cards/classic-quality-business-cards.json',
        'classic-solid-business-cards' => 'content/product-options/business-cards/classic-solid-business-cards.json',
        'basic-cotton-business-card' => 'content/product-options/cotton-business-cards/basic-cotton-business-card.json',
        'classic-cotton-business-card' => 'content/product-options/cotton-business-cards/classic-cotton-business-card.json',
        'premium-cotton-business-card' => 'content/product-options/cotton-business-cards/premium-cotton-business-card.json',
        'luxe-cotton-business-card' => 'content/product-options/cotton-business-cards/luxe-cotton-business-card.json',
        'grand-cotton-business-card' => 'content/product-options/cotton-business-cards/grand-cotton-business-card.json',
        'basic-pvc-card' => 'content/product-options/pvc-business-cards/basic-pvc-card.json',
        'standard-pvc-card' => 'content/product-options/pvc-business-cards/standard-pvc-card.json',
        'premium-pvc-card' => 'content/product-options/pvc-business-cards/premium-pvc-card.json',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::PRODUCT_SUBTITLE_FILES as $slug => $relativePath) {
                $product = DB::table('products')->where('slug', $slug)->first();

                if ($product === null) {
                    continue;
                }

                $path = base_path($relativePath);
                $contents = file_get_contents($path);

                if ($contents === false) {
                    throw new RuntimeException("Unable to read {$path}.");
                }

                $options = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                $subtitle = $options['subtitle'] ?? null;

                if (! is_string($subtitle)) {
                    continue;
                }

                $updates = ['subtitle' => $subtitle];
                $productConfig = is_string($product->product_config ?? null)
                    ? trim($product->product_config)
                    : '';

                if ($productConfig !== '') {
                    $config = json_decode($productConfig, true, 512, JSON_THROW_ON_ERROR);

                    if (is_array($config)) {
                        $config['product'] = is_array($config['product'] ?? null)
                            ? $config['product']
                            : [];
                        $config['product']['subtitle'] = $subtitle;
                        $updates['product_config'] = json_encode(
                            $config,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        );
                    }
                }

                DB::table('products')->where('id', $product->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Product copy is intentionally not reverted so a rollback cannot
        // overwrite edits made in the admin panel after this migration ran.
    }
};
