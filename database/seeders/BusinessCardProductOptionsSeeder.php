<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Support\BusinessCardOptionCatalog;
use App\Support\ClassicStandardBusinessCardGallery;
use App\Support\SolidQualityBusinessCardGallery;
use App\Support\StandardQualityBusinessCardGallery;
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
            'subtitle' => 'Premium metal business cards with engraving, color, or plating options.',
            'description' => '<p>Premium metal business cards with a choice of thickness, size, code or stripe finish, and a signature special finish.</p>',
        ],
        [
            'slug' => 'luxe-metal-business-cards',
            'name' => 'Luxe Metal Business Cards',
            'subtitle' => 'Luxury metal business cards with premium special finishes.',
            'description' => '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, and plating options.</p>',
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const METAL_GALLERIES = [
        'classic-metal-business-cards' => [
            '/images/products/metal/classic-metal-business-cards-01.png',
            '/images/products/metal/classic-metal-business-cards-02.png',
            '/images/products/metal/classic-metal-business-cards-03.png',
            '/images/products/metal/classic-metal-business-cards-04.png',
        ],
        'premium-metal-business-cards' => [
            '/images/products/metal/premium-metal-business-cards-01.png',
            '/images/products/metal/premium-metal-business-cards-02.png',
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

    private const PVC_FINISH_ORDER = ['matte', 'gloss', 'frosted'];

    private const PVC_DEFAULT_FINISH_ORDER = ['matte', 'gloss', 'frosted'];

    /**
     * @var array<int, string>
     */
    private const PVC_BASE_GALLERY = [
        '/images/products/pvc/pvc-01.jpg',
        '/images/products/pvc/pvc-02.jpg',
        '/images/products/pvc/pvc-03.jpg',
        '/images/products/pvc/pvc-04.jpg',
    ];

    /**
     * PVC print-code, no-print-code, and signature-stripe selections use the
     * supplied images as their swatches and selected-gallery primary images.
     *
     * @var array<string, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    private const PVC_OPTION_GALLERY_RULES = [
        'no_print_code' => [
            'id' => 'no_print_code_gallery',
            'match' => ['print_code' => 'no_print_code'],
            'images' => ['/images/product-options/business-cards/swatches/pvc-no-print-code.png'],
            'primary' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
        ],
        'print_code' => [
            'id' => 'print_code_gallery',
            'match' => ['print_code' => 'print_code'],
            'images' => ['/images/products/pvc/pvc-print-code.png'],
            'primary' => '/images/products/pvc/pvc-print-code.png',
        ],
        'signature_stripe' => [
            'id' => 'signature_stripe_gallery',
            'match' => ['print_code_or_signature_stripe' => 'signature_stripe'],
            'images' => ['/images/products/pvc/pvc-signature-stripe.png'],
            'primary' => '/images/products/pvc/pvc-signature-stripe.png',
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
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
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
     * @var array{
     *     default: array<int, string>,
     *     textures: array<string, array{
     *         standard: array{square: string, rounded: string},
     *         square: array{square: string, rounded: string}
     *     }>
     * }
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
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j1-water-ripple-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j1-water-ripple-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j1-water-ripple-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j1-water-ripple-paper.png',
                ],
            ],
            'j2_cloth_texture_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j2-cloth-texture-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j2-cloth-texture-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j2-cloth-texture-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j2-cloth-texture-paper.png',
                ],
            ],
            'j3_eggshell_texture' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j3-eggshell-texture.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j3-eggshell-texture.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j3-eggshell-texture.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j3-eggshell-texture.png',
                ],
            ],
            'j4_high_grade_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j4-high-grade-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j4-high-grade-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j4-high-grade-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j4-high-grade-paper.png',
                ],
            ],
            'j5_pearlescent_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j5-pearlescent-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j5-pearlescent-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j5-pearlescent-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j5-pearlescent-paper.png',
                ],
            ],
            'j6_kraft_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j6-kraft-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j6-kraft-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j6-kraft-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j6-kraft-paper.png',
                ],
            ],
            'j7_absorbent_cotton_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j7-absorbent-cotton-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j7-absorbent-cotton-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j7-absorbent-cotton-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j7-absorbent-cotton-paper.png',
                ],
            ],
            'j8_pinhole_paper' => [
                'standard' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-standard-j8-pinhole-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-rounded-j8-pinhole-paper.png',
                ],
                'square' => [
                    'square' => '/images/products/super-business-cards/super-business-cards-square-j8-pinhole-paper.png',
                    'rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j8-pinhole-paper.png',
                ],
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

                $product->product_category_id = $metalCategory->getKey();

                if (! $product->exists) {
                    $product->forceFill([
                        'name' => $definition['name'],
                        'subtitle' => $definition['subtitle'],
                        'description' => $definition['description'],
                    ]);
                }

                $product->save();

                $config = $this->databaseConfigForProduct($product);

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
                'classic-standard-business-cards',
                'classic-special-business-cards',
                'standard-quality-business-cards',
                'solid-quality-business-cards',
                'basic-pvc-card',
                'standard-pvc-card',
                'premium-pvc-card',
            ] as $slug) {
                $product = Product::query()->where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                $config = $this->canonicalConfigForProduct($product, $configuration);

                if ($slug === ClassicStandardBusinessCardGallery::PRODUCT_SLUG) {
                    $config = ClassicStandardBusinessCardGallery::synchronizeConfig($config);
                }

                if ($slug === StandardQualityBusinessCardGallery::PRODUCT_SLUG) {
                    $config = $configuration->canonicalConfig($product);
                    $config = StandardQualityBusinessCardGallery::synchronizeConfig($config);
                }

                if ($slug === SolidQualityBusinessCardGallery::PRODUCT_SLUG) {
                    $config = SolidQualityBusinessCardGallery::synchronizeConfig($config);
                }

                if ($slug === 'classic-special-business-cards') {
                    $config['media'] = ClassicSpecialBusinessCardOptionsSeeder::synchronizeDefaultGallery(
                        is_array($config['media'] ?? null) ? $config['media'] : [],
                    );
                }

                if (in_array($slug, ['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'], true)) {
                    // Re-apply the central PVC contract so existing rows receive
                    // newly added options and the current swatch asset paths.
                    $config['options'] = BusinessCardOptionCatalog::normalize(
                        $slug,
                        is_array($config['options'] ?? null) ? $config['options'] : [],
                    ) ?? [];
                    $config = $this->withPvcFinishGalleryRules($config, $slug);
                }

                $product->forceFill([
                    'featured_image' => in_array($slug, [
                        ClassicStandardBusinessCardGallery::PRODUCT_SLUG,
                        StandardQualityBusinessCardGallery::PRODUCT_SLUG,
                        SolidQualityBusinessCardGallery::PRODUCT_SLUG,
                    ], true)
                        ? match ($slug) {
                            StandardQualityBusinessCardGallery::PRODUCT_SLUG => StandardQualityBusinessCardGallery::DEFAULT_GALLERY[0],
                            SolidQualityBusinessCardGallery::PRODUCT_SLUG => SolidQualityBusinessCardGallery::DEFAULT_GALLERY[0],
                            default => ClassicStandardBusinessCardGallery::DEFAULT_GALLERY[0],
                        }
                        : $product->featured_image,
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            foreach (self::COTTON_GALLERIES as $slug => $gallery) {
                $product = Product::query()->where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                $config = $this->databaseConfigForProduct($product);
                $config['options'] = BusinessCardOptionCatalog::normalize(
                    $slug,
                    is_array($config['options'] ?? null) ? $config['options'] : [],
                ) ?? [];

                $config['media']['gallery'] = $gallery;

                $galleryRules = is_array($config['media']['gallery_rules'] ?? null)
                    ? $config['media']['gallery_rules']
                    : [];

                $hasDefaultGalleryRule = false;

                foreach ($galleryRules as &$rule) {
                    if (! is_array($rule)) {
                        continue;
                    }

                    $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                    if (($rule['id'] ?? null) === 'default' || $match === []) {
                        if ($hasDefaultGalleryRule) {
                            $rule = null;

                            continue;
                        }

                        $hasDefaultGalleryRule = true;
                        $rule['id'] = 'default';
                        $rule['match'] = [];
                        $rule['images'] = $gallery;
                        $rule['primary'] = $gallery[0];
                    }
                }
                unset($rule);

                $galleryRules = array_values(array_filter(
                    $galleryRules,
                    static fn (mixed $rule): bool => is_array($rule),
                ));

                if (! $hasDefaultGalleryRule) {
                    array_unshift($galleryRules, [
                        'id' => 'default',
                        'match' => [],
                        'images' => $gallery,
                        'primary' => $gallery[0],
                    ]);
                }

                $config['media']['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                    $galleryRules,
                );
                $config['product']['featured_image'] = $gallery[0];
                $product->forceFill([
                    'featured_image' => $gallery[0],
                    'product_config' => $config,
                ])->save();

                $configuration->syncProductProjection($product->fresh());
            }

            $product = Product::query()->where('slug', 'super-luxe-business-cards')->first();

            if ($product) {
                $config = $this->databaseConfigForProduct($product);
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

            $product = Product::query()->where('slug', 'super-standard-business-cards')->first();

            if ($product) {
                $config = $this->databaseConfigForProduct($product);
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
                        'images' => [$images['standard']['square']],
                        'primary' => $images['standard']['square'],
                    ];
                    $galleryRules[] = [
                        'id' => "{$texture}_rounded",
                        'match' => [
                            'sizes' => 'standard',
                            'corners' => 'rounded',
                            'texture' => $texture,
                        ],
                        'images' => [$images['standard']['rounded']],
                        'primary' => $images['standard']['rounded'],
                    ];
                }

                foreach (self::SUPER_BUSINESS_CARD_GALLERY['textures'] as $texture => $images) {
                    $galleryRules[] = [
                        'id' => "{$texture}_square_size_square",
                        'match' => [
                            'sizes' => 'square',
                            'corners' => 'square',
                            'texture' => $texture,
                        ],
                        'images' => [$images['square']['square']],
                        'primary' => $images['square']['square'],
                    ];
                    $galleryRules[] = [
                        'id' => "{$texture}_square_size_rounded",
                        'match' => [
                            'sizes' => 'square',
                            'corners' => 'rounded',
                            'texture' => $texture,
                        ],
                        'images' => [$images['square']['rounded']],
                        'primary' => $images['square']['rounded'],
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

            $this->normalizeOptionalPrintAndDrillingOptionsAcrossProducts();
        });

        if ($this->command !== null) {
            $this->command->info('Business-card product option contracts synchronized.');
        }
    }

    /**
     * The solid-card product keeps its editable legacy option definition in
     * the business-cards content directory. Import only its options and
     * gallery rules; product copy and pricing remain database-owned.
     *
     * @return array<string, mixed>
     */
    private function canonicalConfigForProduct(
        Product $product,
        ProductConfigurationService $configuration,
    ): array {
        $current = $this->databaseConfigForProduct($product);

        if ($product->slug !== 'solid-quality-business-cards') {
            return $current;
        }

        $path = base_path('content/product-options/business-cards/solid-quality-business-cards.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read {$path}.");
        }

        $legacyProduct = clone $product;
        $legacyProduct->setAttribute('product_config', []);
        $legacyProduct->setAttribute('product_options', json_decode(
            $contents,
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));

        $imported = $configuration->canonicalConfig($legacyProduct);

        // This legacy file is retained as an option/gallery source only. Do
        // not copy its subtitle, pricing, FAQ, or detail sections into the
        // database when the maintenance seeder runs.
        $current['options'] = is_array($imported['options'] ?? null)
            ? $imported['options']
            : [];
        $current['media'] = is_array($imported['media'] ?? null)
            ? $imported['media']
            : [];

        return $current;
    }

    /**
     * Keep the PVC galleries pointed at the correct product image for each
     * selected finish or PVC-specific print option.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withPvcFinishGalleryRules(array $config, string $slug): array
    {
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $rules = is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [];
        $defaultImage = BusinessCardOptionCatalog::NO_PRINT_CODE_SWATCH_IMAGE;
        $defaultGallery = is_array($media['gallery'] ?? null)
            ? array_values($media['gallery'])
            : [];
        $finishImages = BusinessCardOptionCatalog::pvcFinishImages($slug);

        if ($defaultGallery === []) {
            foreach ($rules as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                if (($rule['id'] ?? null) === 'default' || $match === []) {
                    $defaultGallery = is_array($rule['images'] ?? null)
                        ? array_values($rule['images'])
                        : [];

                    break;
                }
            }
        }

        if ($defaultGallery === []) {
            $defaultGallery = self::PVC_BASE_GALLERY;
        }

        $finishImagesToRemove = array_values($finishImages);
        $defaultGallery = array_values(array_filter(
            $defaultGallery,
            static fn (mixed $image): bool => is_string($image)
                && $image !== $defaultImage
                && ! in_array($image, $finishImagesToRemove, true),
        ));
        $defaultGallery = [
            $defaultImage,
            ...array_map(
                static fn (string $finish): string => $finishImages[$finish],
                self::PVC_DEFAULT_FINISH_ORDER,
            ),
            ...$defaultGallery,
        ];
        $media['gallery'] = $defaultGallery;
        $finishGalleryRules = $this->pvcFinishGalleryRules($slug);
        $optionGalleryRules = [
            'no_print_code' => array_replace(
                self::PVC_OPTION_GALLERY_RULES['no_print_code'],
                [
                    'match' => $slug === 'standard-pvc-card'
                        ? ['print_code_or_signature_stripe' => 'no_print_code_or_signature_stripe']
                        : ['print_code' => 'no_print_code'],
                ],
            ),
            'print_code' => array_replace(
                self::PVC_OPTION_GALLERY_RULES['print_code'],
                [
                    'match' => $slug === 'standard-pvc-card'
                        ? ['print_code_or_signature_stripe' => 'print_code']
                        : ['print_code' => 'print_code'],
                ],
            ),
        ];

        if ($slug === 'standard-pvc-card') {
            $optionGalleryRules['signature_stripe'] = self::PVC_OPTION_GALLERY_RULES['signature_stripe'];
        }

        $finishCodes = array_keys($finishGalleryRules);
        $finishRuleIds = array_map(
            static fn (string $finish): string => "{$finish}_gallery",
            self::PVC_FINISH_ORDER,
        );

        $rules = array_values(array_filter(
            $rules,
            static function (mixed $rule) use ($finishCodes, $finishRuleIds): bool {
                if (! is_array($rule)) {
                    return false;
                }

                if (in_array((string) ($rule['id'] ?? ''), $finishRuleIds, true)) {
                    return false;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];
                $option = (string) ($match['print_code'] ?? $match['print_code_or_signature_stripe'] ?? '');
                $isUnqualifiedFinishRule = count($match) === 1
                    && array_key_exists('paper_finish', $match)
                    && in_array((string) $match['paper_finish'], $finishCodes, true);

                return ! $isUnqualifiedFinishRule
                    && ! in_array($option, [
                        'no_print_code',
                        'no_print_code_or_signature_stripe',
                        'print_code',
                        'signature_stripe',
                    ], true);
            },
        ));

        $defaultRules = array_values(array_filter(
            $rules,
            static function (array $rule): bool {
                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return $match === [] || ($rule['id'] ?? null) === 'default';
            },
        ));
        $hasDefaultRule = false;

        foreach ($defaultRules as &$rule) {
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if ($match !== [] && ($rule['id'] ?? null) !== 'default') {
                continue;
            }

            $hasDefaultRule = true;
            $rule['images'] = $defaultGallery;
            $rule['primary'] = $defaultImage;
        }
        unset($rule);

        if (! $hasDefaultRule) {
            array_unshift($defaultRules, [
                'id' => 'default',
                'match' => [],
                'images' => $defaultGallery,
                'primary' => $defaultImage,
            ]);
        }

        $otherRules = array_values(array_filter(
            $rules,
            static function (array $rule): bool {
                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return $match !== [] && ($rule['id'] ?? null) !== 'default';
            },
        ));

        $media['gallery_rules'] = [
            ...$defaultRules,
            ...array_values($optionGalleryRules),
            ...$otherRules,
            ...array_values($finishGalleryRules),
        ];
        $config['media'] = $media;

        return $config;
    }

    /**
     * @return array<string, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    private function pvcFinishGalleryRules(string $slug): array
    {
        $finishImages = BusinessCardOptionCatalog::pvcFinishImages($slug);
        $rules = [];

        foreach (self::PVC_FINISH_ORDER as $finish) {
            $primary = $finishImages[$finish];
            $rules[$finish] = [
                'id' => "{$finish}_gallery",
                'match' => ['paper_finish' => $finish],
                'images' => [
                    $primary,
                    '/images/products/pvc/pvc-02.jpg',
                    '/images/products/pvc/pvc-03.jpg',
                    '/images/products/pvc/pvc-04.jpg',
                ],
                'primary' => $primary,
            ];
        }

        return $rules;
    }

    /**
     * Business cards no longer expose NFC, so remove obsolete options and
     * pricing processes when the maintenance seeder runs against an older
     * snapshot.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function removeBusinessCardNfc(array $config): array
    {
        if (is_array($config['options'] ?? null)) {
            $config['options'] = BusinessCardOptionCatalog::withoutNfcOptions($config['options']);
        }

        if (is_array($config['pricing'] ?? null)) {
            $config['pricing'] = $this->removeNfcPricingProcesses($config['pricing']);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    private function removeNfcPricingProcesses(array $pricing): array
    {
        if (is_array($pricing['processes'] ?? null)) {
            $pricing['processes'] = array_values(array_filter(
                $pricing['processes'],
                fn (mixed $process): bool => ! $this->isNfcProcess($process),
            ));
        }

        foreach (['scenarios', 'rules', 'pricing_data'] as $key) {
            if (! is_array($pricing[$key] ?? null)) {
                continue;
            }

            foreach ($pricing[$key] as $entryKey => $entry) {
                if (is_array($entry)) {
                    $pricing[$key][$entryKey] = $this->removeNfcPricingProcesses($entry);
                }
            }
        }

        if (is_array($pricing['pricing'] ?? null)) {
            $pricing['pricing'] = $this->removeNfcPricingProcesses($pricing['pricing']);
        }

        return $pricing;
    }

    private function isNfcProcess(mixed $process): bool
    {
        if (is_scalar($process)) {
            return $this->isNfcToken($process);
        }

        if (! is_array($process)) {
            return false;
        }

        foreach (['code', 'name', 'label'] as $key) {
            if ($this->isNfcToken($process[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function isNfcToken(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        $token = str_replace(['-', ' '], '_', strtolower(trim((string) $value)));

        return in_array($token, ['nfc', 'with_nfc', 'no_nfc'], true);
    }

    /**
     * Return the stored canonical payload without importing repository
     * product content. Galleries and option contracts are updated explicitly
     * by the seeder callers above.
     *
     * @return array<string, mixed>
     */
    private function databaseConfigForProduct(Product $product): array
    {
        $config = is_array($product->product_config) ? $product->product_config : [];

        if (is_array($config['options'] ?? null)) {
            unset($config['options']['special_finish_on_sides']);
            $config['options'] = BusinessCardOptionCatalog::normalizeSharedSwatchImages(
                BusinessCardOptionCatalog::normalizeSharedSizeSwatches(
                    $config['options'],
                    (string) $product->slug,
                ),
            );
            $config['options'] = BusinessCardOptionCatalog::normalizeOptionalPrintAndDrillingOptions(
                $config['options'],
            );
        }

        return $this->removeBusinessCardNfc($config);
    }

    private function normalizeOptionalPrintAndDrillingOptionsAcrossProducts(): void
    {
        Product::query()
            ->orderBy('id')
            ->each(function (Product $product): void {
                $updates = [];
                $config = is_array($product->product_config) ? $product->product_config : [];

                if (is_array($config['options'] ?? null)) {
                    $normalizedOptions = BusinessCardOptionCatalog::normalizeOptionalPrintAndDrillingOptions(
                        $config['options'],
                    );

                    if ($normalizedOptions !== $config['options']) {
                        $config['options'] = $normalizedOptions;
                        $updates['product_config'] = $config;
                    }
                }

                $legacy = is_array($product->product_options) ? $product->product_options : [];

                if ($legacy !== []) {
                    $normalizedLegacy = BusinessCardOptionCatalog::normalizeOptionalPrintAndDrillingOptions($legacy);

                    if ($normalizedLegacy !== $legacy) {
                        $updates['product_options'] = $normalizedLegacy;
                    }
                }

                if ($updates !== []) {
                    $product->forceFill($updates)->save();
                }
            });
    }
}
