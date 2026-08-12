<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCardProductOptionsSeeder extends Seeder
{
    /**
     * @var array<int, array{slug: string, name: string, subtitle: string, description: string}>
     */
    private const METAL_PRODUCTS = [
        [
            'slug' => 'classic-metal-business-cards',
            'name' => 'Classic Metal Business Cards',
            'subtitle' => '0.3mm or 0.5mm metal business cards in three sizes.',
            'description' => '<p>Make a lasting impression with durable metal business cards in your choice of thickness, size, and code or stripe finish.</p>',
        ],
        [
            'slug' => 'premium-metal-business-cards',
            'name' => 'Premium Metal Business Cards',
            'subtitle' => 'Premium metal business cards with engraving, color, plating, or NFC options.',
            'description' => '<p>Premium metal business cards with a choice of thickness, size, code or stripe finish, and a signature special finish.</p>',
        ],
        [
            'slug' => 'luxe-metal-business-cards',
            'name' => 'Luxe Metal Business Cards',
            'subtitle' => 'Luxury metal business cards with premium special finishes.',
            'description' => '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, plating, and NFC options.</p>',
        ],
    ];

    /**
     * @var array<string, array{subtitle: string, description: string}>
     */
    private const PVC_PRODUCT_DETAILS = [
        'basic-pvc-card' => [
            'subtitle' => '0.38mm PVC cards with matte, gloss, or frosted-glass finish options.',
            'description' => '<p>Basic PVC cards at 0.38mm are lightweight, durable, and waterproof.</p>',
        ],
        'standard-pvc-card' => [
            'subtitle' => '0.76mm PVC cards with matte, gloss, frosted-glass, foil, and stripe options.',
            'description' => '<p>Standard PVC cards at 0.76mm offer durable, waterproof construction with flexible print and finish options.</p>',
        ],
        'premium-pvc-card' => [
            'subtitle' => '0.84mm premium PVC NFC cards with optional print code.',
            'description' => '<p>Premium PVC NFC cards at 0.84mm combine durable construction with optional print-code functionality.</p>',
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const COTTON_GALLERIES = [
        'basic-cotton-business-card' => [
            '/images/products/cotton/basic/basic-01.png',
            '/images/products/cotton/basic/basic-02.png',
            '/images/products/cotton/basic/basic-03.png',
            '/images/products/cotton/basic/basic-04.png',
        ],
        'classic-cotton-business-card' => [
            '/images/products/cotton/classic/classic-01.png',
            '/images/products/cotton/classic/classic-02.png',
            '/images/products/cotton/classic/classic-03.png',
            '/images/products/cotton/classic/classic-04.png',
        ],
        'premium-cotton-business-card' => [
            '/images/products/cotton/premium/premium-01.png',
            '/images/products/cotton/premium/premium-02.png',
            '/images/products/cotton/premium/premium-03.png',
            '/images/products/cotton/premium/premium-04.png',
        ],
        'luxe-cotton-business-card' => [
            '/images/products/cotton/luxe/luxe-01.png',
            '/images/products/cotton/luxe/luxe-02.png',
            '/images/products/cotton/luxe/luxe-03.png',
            '/images/products/cotton/luxe/luxe-04.png',
        ],
        'grand-cotton-business-card' => [
            '/images/products/cotton/grand/grand-01.png',
            '/images/products/cotton/grand/grand-02.png',
            '/images/products/cotton/grand/grand-03.png',
            '/images/products/cotton/grand/grand-04.png',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $businessCards = ProductCategory::query()->firstOrCreate(
                ['slug' => 'business-cards'],
                ['name' => 'Business Cards', 'parent_id' => null],
            );

            $metalCategory = ProductCategory::query()->updateOrCreate(
                ['slug' => 'metal-business-cards'],
                [
                    'name' => 'Metal Business Cards',
                    'parent_id' => $businessCards->getKey(),
                ],
            );

            $configuration = app(ProductConfigurationService::class);

            foreach (self::METAL_PRODUCTS as $definition) {
                $product = Product::query()->firstOrNew(['slug' => $definition['slug']]);

                if (! $product->exists) {
                    $product->forceFill([
                        'name' => $definition['name'],
                        'subtitle' => $definition['subtitle'],
                        'description' => $definition['description'],
                        'price_line' => null,
                        'is_active' => true,
                    ]);
                }

                $product->product_category_id = $metalCategory->getKey();
                $product->save();

                $product->forceFill([
                    'product_config' => $configuration->canonicalConfig($product),
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            foreach ([
                'classic-quality-business-cards',
                'basic-pvc-card',
                'standard-pvc-card',
                'premium-pvc-card',
            ] as $slug) {
                $product = Product::query()->where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                if (isset(self::PVC_PRODUCT_DETAILS[$slug])) {
                    $product->forceFill(self::PVC_PRODUCT_DETAILS[$slug])->save();
                }

                $product->forceFill([
                    'product_config' => $configuration->canonicalConfig($product),
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            foreach (self::COTTON_GALLERIES as $slug => $gallery) {
                $product = Product::query()->where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                $config = $configuration->canonicalConfig($product);
                $config['media']['gallery'] = $gallery;

                $galleryRules = is_array($config['media']['gallery_rules'] ?? null)
                    ? $config['media']['gallery_rules']
                    : [];

                foreach ($galleryRules as &$rule) {
                    if (is_array($rule) && (($rule['id'] ?? null) === 'default' || ($rule['match'] ?? []) === [])) {
                        $rule['images'] = $gallery;
                        $rule['primary'] = $gallery[0];
                    }
                }
                unset($rule);

                $config['media']['gallery_rules'] = $galleryRules;
                $product->forceFill([
                    'featured_image' => $gallery[0],
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }
        });

        if ($this->command !== null) {
            $this->command->info('Business-card product option contracts synchronized.');
        }
    }
}
