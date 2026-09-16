<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Database\Seeders\BusinessCardProductOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PvcDefaultGalleryTest extends TestCase
{
    use RefreshDatabase;

    private const NO_PRINT_CODE_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    /**
     * @var list<string>
     */
    private const PVC_PRODUCT_SLUGS = [
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const PVC_FINISH_IMAGES = [
        'basic-pvc-card' => [
            'matte' => '/images/products/pvc/basic-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/basic-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/basic-pvc-card-frosted.png',
        ],
        'standard-pvc-card' => [
            'matte' => '/images/products/pvc/standard-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/standard-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/standard-pvc-card-frosted.png',
        ],
        'premium-pvc-card' => [
            'matte' => '/images/products/pvc/premium-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/premium-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/premium-pvc-card-frosted.png',
        ],
    ];

    public function test_migration_makes_the_no_print_code_swatch_the_default_gallery_image(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $originalGallery = $this->originalGallery();
        $featuredImage = $originalGallery[0];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $featuredImage,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => ['featured_image' => $featuredImage],
                    'media' => [
                        'gallery' => $originalGallery,
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => $originalGallery,
                                'primary' => $featuredImage,
                            ],
                            [
                                'id' => 'keep-gallery',
                                'match' => ['special_finish' => 'keep'],
                                'images' => ['/images/keep-gallery.png'],
                                'primary' => '/images/keep-gallery.png',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_17_000001_make_pvc_no_print_code_default_gallery.php',
        );
        $migration->up();
        $migration->up();

        $expectedGallery = [self::NO_PRINT_CODE_IMAGE, ...$originalGallery];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $config = $product->product_config;
            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');

            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));
            $this->assertSame($featuredImage, $product->featured_image);
            $this->assertSame($featuredImage, data_get($config, 'product.featured_image'));
            $this->assertSame(
                '/images/keep-gallery.png',
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'keep-gallery'),
                    'primary',
                ),
            );
        }
    }

    public function test_finish_asset_migration_updates_all_pvc_finish_sources_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $originalGallery = $this->originalGallery();
        $featuredImage = $originalGallery[0];
        $oldFinishImages = [
            'matte' => '/images/products/pvc/matte-pvc-main.png',
            'gloss' => '/images/products/pvc/gloss-pvc-main.png',
            'frosted' => '/images/products/pvc/frosted-pvc-main.png',
        ];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $featuredImage,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => ['featured_image' => $featuredImage],
                    'options' => [
                        'paper_finish' => [
                            'label' => 'Paper Finish',
                            'type' => 'select',
                            'required' => true,
                            'default' => 'matte',
                            'values' => [
                                ['code' => 'matte', 'label' => 'Matte', 'swatch_image' => $oldFinishImages['matte']],
                                ['code' => 'gloss', 'label' => 'Gloss', 'swatch_image' => $oldFinishImages['gloss']],
                                ['code' => 'frosted', 'label' => 'Frosted Glass', 'swatch_image' => $oldFinishImages['frosted']],
                            ],
                        ],
                    ],
                    'media' => [
                        'gallery' => $originalGallery,
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => $originalGallery,
                                'primary' => $featuredImage,
                            ],
                            [
                                'id' => 'keep-gallery',
                                'match' => ['special_finish' => 'keep'],
                                'images' => ['/images/keep-gallery.png'],
                                'primary' => '/images/keep-gallery.png',
                            ],
                            ...array_map(
                                static fn (string $finish): array => [
                                    'id' => "{$finish}_gallery",
                                    'match' => ['paper_finish' => $finish],
                                    'images' => [$oldFinishImages[$finish]],
                                    'primary' => $oldFinishImages[$finish],
                                ],
                                array_keys($oldFinishImages),
                            ),
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_17_000002_update_pvc_finish_gallery_assets.php',
        );
        $migration->up();
        $migration->up();

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $config = $product->product_config;
            $finishImages = self::PVC_FINISH_IMAGES[$slug];
            $expectedGallery = $this->expectedGalleryAfterAssetMigration($slug, $originalGallery);

            $this->assertSame(
                [$finishImages['matte'], $finishImages['gloss'], $finishImages['frosted']],
                data_get($config, 'options.paper_finish.values.*.swatch_image'),
            );
            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));

            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));

            foreach ($finishImages as $finish => $primary) {
                $rule = collect(data_get($config, 'media.gallery_rules', []))
                    ->firstWhere('id', "{$finish}_gallery");

                $this->assertSame(
                    [$primary, ...array_slice($originalGallery, 1)],
                    data_get($rule, 'images'),
                );
                $this->assertSame($primary, data_get($rule, 'primary'));
            }

            $this->assertSame(
                '/images/keep-gallery.png',
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'keep-gallery'),
                    'primary',
                ),
            );
            $this->assertSame($featuredImage, $product->featured_image);
            $this->assertSame($featuredImage, data_get($config, 'product.featured_image'));
        }
    }

    public function test_default_gallery_order_migration_replaces_the_previous_finish_order_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $originalGallery = $this->originalGallery();
        $featuredImage = $originalGallery[0];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $finishImages = self::PVC_FINISH_IMAGES[$slug];
            $oldDefaultGallery = [
                self::NO_PRINT_CODE_IMAGE,
                ...$originalGallery,
                $finishImages['gloss'],
                $finishImages['matte'],
                $finishImages['frosted'],
            ];

            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $featuredImage,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => ['featured_image' => $featuredImage],
                    'media' => [
                        'gallery' => $oldDefaultGallery,
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => $oldDefaultGallery,
                                'primary' => self::NO_PRINT_CODE_IMAGE,
                            ],
                            [
                                'id' => 'keep-gallery',
                                'match' => ['special_finish' => 'keep'],
                                'images' => ['/images/keep-gallery.png'],
                                'primary' => '/images/keep-gallery.png',
                            ],
                            [
                                'id' => 'matte_gallery',
                                'match' => ['paper_finish' => 'matte'],
                                'images' => ['/images/keep-matte-gallery.png'],
                                'primary' => '/images/keep-matte-gallery.png',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_17_000003_reorder_pvc_default_gallery_finish_assets.php',
        );
        $migration->up();
        $migration->up();

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $config = $product->product_config;
            $expectedGallery = $this->expectedGalleryAfterAssetMigration($slug, $originalGallery);
            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');

            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));
            $this->assertSame(
                '/images/keep-gallery.png',
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'keep-gallery'),
                    'primary',
                ),
            );
            $this->assertSame(
                ['/images/keep-matte-gallery.png'],
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'matte_gallery'),
                    'images',
                ),
            );
            $this->assertSame($featuredImage, $product->featured_image);
            $this->assertSame($featuredImage, data_get($config, 'product.featured_image'));
        }
    }

    public function test_thumbnail_position_migration_moves_finish_images_into_the_first_four_positions_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $originalGallery = $this->originalGallery();
        $featuredImage = $originalGallery[0];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $finishImages = self::PVC_FINISH_IMAGES[$slug];
            $oldDefaultGallery = [
                self::NO_PRINT_CODE_IMAGE,
                ...$originalGallery,
                $finishImages['matte'],
                $finishImages['gloss'],
                $finishImages['frosted'],
            ];

            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $featuredImage,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => ['featured_image' => $featuredImage],
                    'media' => [
                        'gallery' => $oldDefaultGallery,
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => $oldDefaultGallery,
                                'primary' => self::NO_PRINT_CODE_IMAGE,
                            ],
                            [
                                'id' => 'keep-gallery',
                                'match' => ['special_finish' => 'keep'],
                                'images' => ['/images/keep-gallery.png'],
                                'primary' => '/images/keep-gallery.png',
                            ],
                            [
                                'id' => 'matte_gallery',
                                'match' => ['paper_finish' => 'matte'],
                                'images' => ['/images/keep-matte-gallery.png'],
                                'primary' => '/images/keep-matte-gallery.png',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_17_000004_move_pvc_finish_images_into_default_gallery_positions.php',
        );
        $migration->up();
        $migration->up();

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $config = $product->product_config;
            $expectedGallery = $this->expectedGallery($slug, $originalGallery);
            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');

            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));
            $this->assertSame(
                '/images/keep-gallery.png',
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'keep-gallery'),
                    'primary',
                ),
            );
            $this->assertSame(
                ['/images/keep-matte-gallery.png'],
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'matte_gallery'),
                    'images',
                ),
            );
            $this->assertSame($featuredImage, $product->featured_image);
            $this->assertSame($featuredImage, data_get($config, 'product.featured_image'));
        }
    }

    public function test_seeder_keeps_the_no_print_code_swatch_first_on_repeated_runs(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $originalGallery = $this->originalGallery();
        $featuredImage = $originalGallery[0];

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $featuredImage,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => ['featured_image' => $featuredImage],
                    'media' => [
                        'gallery' => $originalGallery,
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => $originalGallery,
                                'primary' => $featuredImage,
                            ],
                            [
                                'id' => 'keep-gallery',
                                'match' => ['special_finish' => 'keep'],
                                'images' => ['/images/keep-gallery.png'],
                                'primary' => '/images/keep-gallery.png',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();
        (new BusinessCardProductOptionsSeeder)->run();

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $expectedGallery = $this->expectedGallery($slug, $originalGallery);
            $product = Product::where('slug', $slug)->firstOrFail();
            $config = $product->product_config;
            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');
            $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($storefront, 'galleries.0.images.0'));
            $this->assertSame($featuredImage, $product->featured_image);
            $this->assertSame(
                '/images/keep-gallery.png',
                data_get(
                    collect(data_get($config, 'media.gallery_rules', []))->firstWhere('id', 'keep-gallery'),
                    'primary',
                ),
            );
        }
    }

    public function test_legacy_sources_and_canonical_snapshot_start_with_the_no_print_code_swatch(): void
    {
        $category = new ProductCategory([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $expectedGallery = $this->expectedGallery($slug, $this->originalGallery());
            $contents = File::get(
                base_path("content/product-options/pvc-business-cards/{$slug}.json"),
            );
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $defaultGallery = collect($payload['galleries'] ?? [])->firstWhere('id', 'default');

            $this->assertSame($expectedGallery, data_get($defaultGallery, 'images'));

            $product = new Product([
                'name' => $slug,
                'slug' => $slug,
                'featured_image' => $this->originalGallery()[0],
                'product_options' => $payload,
            ]);
            $product->setRelation('category', $category);

            $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($storefront, 'galleries.0.images.0'));
        }

        $snapshot = json_decode(
            File::get(database_path('seeders/data/products.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $expectedGallery = $this->expectedGallery($slug, $this->originalGallery());
            $product = collect($snapshot)->firstWhere('slug', $slug);
            $config = json_decode($product['product_config'], true, 512, JSON_THROW_ON_ERROR);
            $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'default');

            $this->assertSame($expectedGallery, data_get($config, 'media.gallery'));
            $this->assertSame($expectedGallery, data_get($defaultRule, 'images'));
            $this->assertSame(self::NO_PRINT_CODE_IMAGE, data_get($defaultRule, 'primary'));
            $this->assertSame(
                $this->originalGallery()[0],
                data_get($config, 'product.featured_image'),
            );
            $this->assertSame($this->originalGallery()[0], $product['featured_image']);
        }
    }

    /**
     * @return list<string>
     */
    private function originalGallery(): array
    {
        return [
            '/images/products/pvc/pvc-01.jpg',
            '/images/products/pvc/pvc-02.jpg',
            '/images/products/pvc/pvc-03.jpg',
            '/images/products/pvc/pvc-04.jpg',
        ];
    }

    /**
     * @param  list<string>  $originalGallery
     * @return list<string>
     */
    private function expectedGallery(string $slug, array $originalGallery): array
    {
        $finishImages = self::PVC_FINISH_IMAGES[$slug];

        return [
            self::NO_PRINT_CODE_IMAGE,
            $finishImages['matte'],
            $finishImages['gloss'],
            $finishImages['frosted'],
            ...$originalGallery,
        ];
    }

    /**
     * @param  list<string>  $originalGallery
     * @return list<string>
     */
    private function expectedGalleryAfterAssetMigration(string $slug, array $originalGallery): array
    {
        $finishImages = self::PVC_FINISH_IMAGES[$slug];

        return [
            self::NO_PRINT_CODE_IMAGE,
            ...$originalGallery,
            $finishImages['matte'],
            $finishImages['gloss'],
            $finishImages['frosted'],
        ];
    }
}
