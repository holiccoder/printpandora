<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Database\Seeders\BusinessCardProductOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedBusinessCardSizeSwatchesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function businessCardSizeProducts(): array
    {
        return [
            'classic-standard-business-cards' => 'classic-standard-business-cards.json',
            'classic-special-business-cards' => 'classic-special-business-cards.json',
            'standard-quality-business-cards' => 'standard-quality-business-cards.json',
            'solid-quality-business-cards' => 'solid-quality-business-cards.json',
            'super-luxe-business-cards' => 'super-luxe-business-cards.json',
            'super-standard-business-cards' => 'super-standard-business-cards.json',
        ];
    }

    private function expectedSizeSwatch(string $slug, string $code): string
    {
        return [
            'standard' => '/images/product-options/business-cards/swatches/standard-size.webp',
            'square' => '/images/product-options/business-cards/swatches/square-size.webp',
        ][$code];
    }

    public function test_canonical_configs_use_the_shared_size_metadata(): void
    {
        $configuration = app(ProductConfigurationService::class);

        foreach (array_keys($this->businessCardSizeProducts()) as $slug) {
            $product = new Product([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_config' => [
                    'options' => [
                        'sizes' => [
                            'values' => [
                                ['code' => 'standard', 'swatch_image' => '/old-standard.png'],
                                ['code' => 'square', 'swatch_image' => '/old-square.png'],
                                ...($slug === 'solid-quality-business-cards'
                                    ? []
                                    : [['code' => 'custom', 'description' => 'old custom description']]),
                            ],
                        ],
                    ],
                ],
            ]);

            $values = $this->keyedSizeValues($configuration->canonicalConfig($product));

            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'standard'),
                data_get($values, 'standard.swatch_image'),
                $slug,
            );
            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'square'),
                data_get($values, 'square.swatch_image'),
                $slug,
            );

            if ($slug === 'classic-standard-business-cards') {
                $this->assertSame(
                    '2.1 - 3.5 inches',
                    data_get($values, 'custom.description'),
                    $slug,
                );
            } else {
                $this->assertSame(
                    'max range: 2.1 - 3.5 inches',
                    data_get($values, 'custom.description'),
                    $slug,
                );
            }
        }
    }

    public function test_legacy_configs_receive_the_same_metadata_without_changing_pvc_options(): void
    {
        $configuration = app(ProductConfigurationService::class);
        $category = new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach ($this->businessCardSizeProducts() as $slug => $filename) {
            $contents = file_get_contents(base_path("content/product-options/business-cards/{$filename}"));

            if ($contents === false) {
                $this->fail("The {$slug} legacy options file could not be read.");
            }

            $legacy = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($legacy['sizes'] ?? null)) {
                foreach ($legacy['sizes'] as &$size) {
                    if (! is_array($size)) {
                        continue;
                    }

                    $size['swatch_image'] = match ($size['code'] ?? null) {
                        'standard' => '/legacy-standard.png',
                        'square' => '/legacy-square.png',
                        default => $size['swatch_image'] ?? null,
                    };

                    if (($size['code'] ?? null) === 'custom') {
                        $size['description'] = 'Legacy custom range';
                    }
                }
                unset($size);
            }

            $product = new Product([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_options' => $legacy,
            ]);
            $product->setRelation('category', $category);

            $values = $this->keyedSizeValues($configuration->canonicalConfig($product));

            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'standard'),
                data_get($values, 'standard.swatch_image'),
                $slug,
            );
            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'square'),
                data_get($values, 'square.swatch_image'),
                $slug,
            );

            $storefront = $configuration->storefrontOptions($product);
            $this->assertIsArray($storefront);
            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'standard'),
                data_get($storefront, 'sizes.0.swatch_image'),
                $slug,
            );
            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'square'),
                data_get($storefront, 'sizes.1.swatch_image'),
                $slug,
            );

            if ($slug === 'classic-standard-business-cards') {
                $this->assertSame(
                    '2.1 - 3.5 inches',
                    data_get($values, 'custom.description'),
                    $slug,
                );
                $this->assertSame(
                    '2.1 - 3.5 inches',
                    data_get($storefront, 'sizes.2.description'),
                    $slug,
                );
            } else {
                $this->assertSame(
                    'max range: 2.1 - 3.5 inches',
                    data_get($values, 'custom.description'),
                    $slug,
                );
                $this->assertSame(
                    'max range: 2.1 - 3.5 inches',
                    data_get($storefront, 'sizes.2.description'),
                    $slug,
                );
            }
        }

        $pvcContents = file_get_contents(base_path('content/product-options/pvc-business-cards/basic-pvc-card.json'));

        if ($pvcContents === false) {
            $this->fail('The Basic PVC legacy options file could not be read.');
        }

        $pvc = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_options' => json_decode($pvcContents, true, 512, JSON_THROW_ON_ERROR),
        ]);
        $pvc->setRelation('category', new ProductCategory([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]));

        $this->assertArrayNotHasKey(
            'sizes',
            $configuration->canonicalConfig($pvc)['options'],
        );
    }

    public function test_business_card_seeder_updates_existing_canonical_size_metadata(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach (array_keys($this->businessCardSizeProducts()) as $slug) {
            Product::create([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'product_config' => [
                    'product' => [],
                    'options' => [
                        'sizes' => [
                            'values' => [
                                ['code' => 'standard', 'swatch_image' => '/old-standard.png'],
                                ['code' => 'square', 'swatch_image' => '/old-square.png'],
                                ...($slug === 'solid-quality-business-cards'
                                    ? []
                                    : [['code' => 'custom', 'description' => 'old custom description']]),
                            ],
                        ],
                    ],
                    'media' => [
                        'gallery' => [],
                        'gallery_rules' => [],
                    ],
                ],
                'is_active' => true,
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        foreach (array_keys($this->businessCardSizeProducts()) as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'standard'),
                data_get($config, 'options.sizes.values.0.swatch_image'),
                $slug,
            );
            $this->assertSame(
                $this->expectedSizeSwatch($slug, 'square'),
                data_get($config, 'options.sizes.values.1.swatch_image'),
                $slug,
            );

            if ($slug === 'classic-standard-business-cards') {
                $this->assertSame(
                    '2.1 - 3.5 inches',
                    data_get($config, 'options.sizes.values.2.description'),
                    $slug,
                );
            } else {
                $this->assertSame(
                    'max range: 2.1 - 3.5 inches',
                    data_get($config, 'options.sizes.values.2.description'),
                    $slug,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function keyedSizeValues(array $config): array
    {
        $values = data_get($config, 'options.sizes.values', []);
        $indexed = [];

        if (! is_array($values)) {
            return $indexed;
        }

        foreach ($values as $value) {
            if (! is_array($value) || ! is_string($value['code'] ?? null)) {
                continue;
            }

            $indexed[$value['code']] = $value;
        }

        return $indexed;
    }
}
