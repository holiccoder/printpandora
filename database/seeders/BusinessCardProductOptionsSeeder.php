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
            'subtitle' => '12pt or 20pt metal business cards in three sizes.',
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
     * @var array<string, array<int, string>>
     */
    private const METAL_GALLERIES = [
        'classic-metal-business-cards' => [
            '/images/products/metal/classic-metal-business-cards-04.png',
            '/images/products/metal/classic-metal-business-cards-02.png',
            '/images/products/metal/classic-metal-business-cards-03.png',
            '/images/products/metal/classic-metal-business-cards-01.png',
        ],
        'premium-metal-business-cards' => [
            '/images/products/metal/premium-metal-business-cards-02.png',
            '/images/products/metal/premium-metal-business-cards-01.png',
            '/images/products/metal/premium-metal-business-cards-03.png',
            '/images/products/metal/premium-metal-business-cards-04.png',
        ],
        'luxe-metal-business-cards' => [
            '/images/products/metal/luxe-metal-business-cards-04.png',
            '/images/products/metal/luxe-metal-business-cards-02.png',
            '/images/products/metal/luxe-metal-business-cards-03.png',
            '/images/products/metal/luxe-metal-business-cards-01.png',
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

    /**
     * @var array{default: array<int, string>, textures: array<string, string>}
     */
    private const LUXE_BUSINESS_CARD_GALLERY = [
        'default' => [
            '/images/products/luxe-business-cards/luxe-business-cards-standard-01.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-02.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-03.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-04.png',
        ],
        'textures' => [
            'inkpavo_j1' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j1.png',
            'inkpavo_j2' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j2.png',
            'inkpavo_j3' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j3.png',
            'inkpavo_j4' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j4.png',
            'inkpavo_j5' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j5.png',
            'inkpavo_j6' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j6.png',
            'inkpavo_j7' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j7.png',
            'inkpavo_j8' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j8.png',
        ],
    ];

    /**
     * @var array{default: array<int, string>, textures: array<string, array{standard: string, rounded: string}>}
     */
    private const SUPER_BUSINESS_CARD_GALLERY = [
        'default' => [
            '/images/products/super-business-cards/super-business-cards-default-01.png',
            '/images/products/super-business-cards/super-business-cards-default-02.png',
            '/images/products/super-business-cards/super-business-cards-default-03.png',
            '/images/products/super-business-cards/super-business-cards-default-04.png',
        ],
        'textures' => [
            'j1_water_ripple_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j1-water-ripple-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j1-water-ripple-paper.png',
            ],
            'j2_cloth_texture_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j2-cloth-texture-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j2-cloth-texture-paper.png',
            ],
            'j3_eggshell_texture' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j3-eggshell-texture.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j3-eggshell-texture.png',
            ],
            'j4_high_grade_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j4-high-grade-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j4-high-grade-paper.png',
            ],
            'j5_pearlescent_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j5-pearlescent-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j5-pearlescent-paper.png',
            ],
            'j6_kraft_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j6-kraft-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j6-kraft-paper.png',
            ],
            'j7_absorbent_cotton_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j7-absorbent-cotton-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j7-absorbent-cotton-paper.png',
            ],
            'j8_pinhole_paper' => [
                'standard' => '/images/products/super-business-cards/super-business-cards-standard-j8-pinhole-paper.png',
                'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j8-pinhole-paper.png',
            ],
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
                        'price_line' => null,
                        'is_active' => true,
                    ]);
                }

                $product->name = $definition['name'];
                $product->subtitle = $definition['subtitle'];
                $product->description = $definition['description'];
                $product->product_category_id = $metalCategory->getKey();
                $product->save();

                $config = $configuration->canonicalConfig($product);
                $pricingRules = $configuration->dynamicPricingRules($product);

                if ($pricingRules !== null) {
                    $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
                    $config['pricing'] = array_replace($pricing, [
                        'mode' => 'rule_based',
                        'currency' => (string) ($pricing['currency'] ?? 'USD'),
                        'total_rounding' => (string) ($pricing['total_rounding'] ?? 'nearest_integer'),
                        'rules' => $pricingRules,
                        'scenarios' => [],
                        'quantity_price_table' => [],
                    ]);
                }

                $gallery = self::METAL_GALLERIES[$definition['slug']];
                $config['media']['gallery'] = $gallery;

                $galleryRules = is_array($config['media']['gallery_rules'] ?? null)
                    ? $config['media']['gallery_rules']
                    : [];
                $hasDefaultGalleryRule = false;

                foreach ($galleryRules as &$rule) {
                    if (is_array($rule) && (($rule['id'] ?? null) === 'default' || ($rule['match'] ?? []) === [])) {
                        $hasDefaultGalleryRule = true;
                        $rule['images'] = $gallery;
                        $rule['primary'] = $gallery[0];
                    }
                }
                unset($rule);

                if (! $hasDefaultGalleryRule) {
                    $galleryRules[] = [
                        'id' => 'default',
                        'match' => [],
                        'images' => $gallery,
                        'primary' => $gallery[0],
                    ];
                }

                $config['media']['gallery_rules'] = $galleryRules;
                $config['product']['featured_image'] = $gallery[0];
                $product->forceFill([
                    'featured_image' => $gallery[0],
                    'product_config' => $config,
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

                $config = $configuration->canonicalConfig($product);
                $pricingScenarios = $configuration->dynamicPricingScenarios($product);

                if ($pricingScenarios !== null) {
                    $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
                    $config['pricing'] = array_replace($pricing, [
                        'mode' => 'rule_based',
                        'currency' => (string) ($pricing['currency'] ?? 'USD'),
                        'total_rounding' => (string) ($pricing['total_rounding'] ?? 'nearest_integer'),
                        'rules' => [],
                        'scenarios' => $pricingScenarios,
                        'quantity_price_table' => [],
                    ]);
                }

                $product->forceFill([
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            foreach (self::COTTON_GALLERIES as $slug => $gallery) {
                $product = Product::query()->where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                $config = $configuration->canonicalConfig($product);
                $pricingScenarios = $configuration->dynamicPricingScenarios($product);

                if ($pricingScenarios !== null) {
                    $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
                    $config['pricing'] = array_replace($pricing, [
                        'mode' => 'rule_based',
                        'currency' => (string) ($pricing['currency'] ?? 'USD'),
                        'total_rounding' => (string) ($pricing['total_rounding'] ?? 'nearest_integer'),
                        'rules' => [],
                        'scenarios' => $pricingScenarios,
                        'quantity_price_table' => [],
                    ]);
                }

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
                $config['product']['featured_image'] = $gallery[0];
                $product->forceFill([
                    'featured_image' => $gallery[0],
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            $product = Product::query()->where('slug', 'luxe-business-cards')->first();

            if ($product) {
                $config = $configuration->canonicalConfig($product);
                $defaultGallery = self::LUXE_BUSINESS_CARD_GALLERY['default'];
                $galleryRules = [[
                    'id' => 'default',
                    'match' => [],
                    'images' => $defaultGallery,
                    'primary' => $defaultGallery[0],
                ]];

                foreach (self::LUXE_BUSINESS_CARD_GALLERY['textures'] as $texture => $image) {
                    $galleryRules[] = [
                        'id' => $texture,
                        'match' => [
                            'sizes' => 'standard',
                            'texture' => $texture,
                        ],
                        'images' => [$image],
                        'primary' => $image,
                    ];
                }

                $config['media']['gallery'] = $defaultGallery;
                $config['media']['gallery_rules'] = $galleryRules;
                $config['product']['featured_image'] = $defaultGallery[0];
                $product->forceFill([
                    'featured_image' => $defaultGallery[0],
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            $product = Product::query()->where('slug', 'super-business-cards')->first();

            if ($product) {
                $config = $configuration->canonicalConfig($product);
                $defaultGallery = self::SUPER_BUSINESS_CARD_GALLERY['default'];
                $galleryRules = [[
                    'id' => 'default',
                    'match' => [],
                    'images' => $defaultGallery,
                    'primary' => $defaultGallery[0],
                ]];

                foreach (self::SUPER_BUSINESS_CARD_GALLERY['textures'] as $texture => $images) {
                    $galleryRules[] = [
                        'id' => "{$texture}_square",
                        'match' => [
                            'sizes' => 'standard',
                            'corners' => 'square',
                            'texture' => $texture,
                        ],
                        'images' => [$images['standard']],
                        'primary' => $images['standard'],
                    ];
                    $galleryRules[] = [
                        'id' => "{$texture}_rounded",
                        'match' => [
                            'sizes' => 'standard',
                            'corners' => 'rounded',
                            'texture' => $texture,
                        ],
                        'images' => [$images['rounded']],
                        'primary' => $images['rounded'],
                    ];
                }

                $config['media']['gallery'] = $defaultGallery;
                $config['media']['gallery_rules'] = $galleryRules;
                $config['product']['featured_image'] = $defaultGallery[0];
                $product->forceFill([
                    'featured_image' => $defaultGallery[0],
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
