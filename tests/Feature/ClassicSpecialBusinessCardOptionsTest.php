<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Support\ClassicSpecialBusinessCardTexture;
use Database\Seeders\ClassicSpecialBusinessCardOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassicSpecialBusinessCardOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_imports_the_requested_classic_special_options(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $product = Product::create([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [],
            'is_active' => true,
        ]);

        (new ClassicSpecialBusinessCardOptionsSeeder)->run();
        (new ClassicSpecialBusinessCardOptionsSeeder)->run();

        $product->refresh();

        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($product->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($product->product_config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($product->product_config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($product->product_config, 'options.sizes.values.2.description'),
        );
        $defaultGallery = [
            '/images/classic-special-business-cards/default01.png',
            '/images/classic-special-business-cards/default02.png',
            '/images/classic-special-business-cards/default03.png',
            '/images/classic-special-business-cards/default04.png',
        ];
        $this->assertSame($defaultGallery, data_get($product->product_config, 'media.gallery'));
        $this->assertSame($defaultGallery, data_get($product->product_config, 'media.gallery_rules.0.images'));
        $this->assertSame(
            [ClassicSpecialBusinessCardTexture::SQUARE_IMAGE],
            data_get($product->product_config, 'media.gallery_rules.1.images'),
        );
        $this->assertSame(
            ClassicSpecialBusinessCardTexture::SQUARE_IMAGE,
            data_get($product->product_config, 'media.gallery_rules.1.primary'),
        );
        $this->assertSame(
            [ClassicSpecialBusinessCardTexture::ROUNDED_IMAGE],
            data_get($product->product_config, 'media.gallery_rules.2.images'),
        );
        $this->assertSame(
            ClassicSpecialBusinessCardTexture::ROUNDED_IMAGE,
            data_get($product->product_config, 'media.gallery_rules.2.primary'),
        );
        foreach ([ClassicSpecialBusinessCardTexture::SQUARE_IMAGE, ClassicSpecialBusinessCardTexture::ROUNDED_IMAGE] as $image) {
            $this->assertFileExists(public_path(ltrim($image, '/')));
            $this->assertFileExists(public_path(ltrim(str_replace('.png', '.webp', $image), '/')));
        }
        $galleryRulesById = [];
        foreach (data_get($product->product_config, 'media.gallery_rules', []) as $rule) {
            if (is_array($rule) && isset($rule['id'])) {
                $galleryRulesById[$rule['id']] = $rule;
            }
        }
        foreach (ClassicSpecialBusinessCardTexture::mappedGalleryRules() as $expectedRule) {
            $actualRule = $galleryRulesById[$expectedRule['id']] ?? null;

            $this->assertIsArray($actualRule);
            $this->assertSame($expectedRule['match'], $actualRule['match'] ?? null);
            $this->assertSame($expectedRule['images'], $actualRule['images'] ?? null);
            $this->assertSame($expectedRule['primary'], $actualRule['primary'] ?? null);
            $this->assertFileExists(public_path(ltrim($expectedRule['images'][0], '/')));
            $this->assertFileExists(public_path(ltrim(str_replace('.png', '.webp', $expectedRule['images'][0]), '/')));
        }
        $this->assertArrayNotHasKey('paper_finish', $product->product_config['options']);
        $this->assertSame(
            ['square', 'rounded'],
            data_get($product->product_config, 'options.corners.values.*.code'),
        );
        $this->assertSame(
            [
                'black_gold',
                'blue_gold',
                'bright_gold',
                'bright_silver',
                'green_gold',
                'matte_gold',
                'matte_silver',
                'red_gold',
                'rose_gold',
                'aged_gold',
                'muted_purple_gold',
            ],
            data_get($product->product_config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame(
            'multi_select',
            data_get($product->product_config, 'options.special_finish.type'),
        );
        $this->assertFalse(data_get($product->product_config, 'options.special_finish.required'));
        $this->assertSame([], data_get($product->product_config, 'options.special_finish.default'));
        $this->assertSame(
            'water_ripple_paper',
            data_get($product->product_config, 'options.texture.default'),
        );
        $this->assertSame(
            [
                'water_ripple_paper',
                'linen_paper',
                'eggshell_paper',
                'white_cardstock',
                'pearlized_paper',
                'pin_point_embossed_paper',
            ],
            data_get($product->product_config, 'options.texture.values.*.code'),
        );
        $this->assertSame(
            [
                '/images/products/classic-special-business-cards/texture/water-ripple-paper.png',
                '/images/products/classic-special-business-cards/texture/linen-paper.png',
                '/images/products/classic-special-business-cards/texture/eggshell-paper.png',
                '/images/products/classic-special-business-cards/texture/white-cardstock.png',
                '/images/products/classic-special-business-cards/texture/pearlized-paper.png',
                ClassicSpecialBusinessCardTexture::SQUARE_IMAGE,
            ],
            data_get($product->product_config, 'options.texture.values.*.swatch_image'),
        );
        $this->assertSame(
            ClassicSpecialBusinessCardTexture::LABEL,
            data_get($product->product_config, 'options.texture.values.5.label'),
        );
        $this->assertArrayNotHasKey('print_code', $product->product_config['options']);
        $this->assertArrayNotHasKey('drill', $product->product_config['options']);
        $this->assertArrayNotHasKey('special_finish_on_sides', $product->product_config['options']);
    }

    public function test_legacy_and_canonical_configs_expose_the_same_option_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = new Product([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_config' => [
                'options' => [
                    'sizes' => ['values' => [['code' => 'standard'], ['code' => 'square']]],
                    'paper_finish' => ['values' => [['code' => 'matte'], ['code' => 'gloss']]],
                    'corners' => ['values' => [['code' => 'square'], ['code' => 'rounded']]],
                    'special_finish' => ['values' => [['code' => 'no_special_finish']]],
                    'print_code' => ['values' => [['code' => 'no_print_code']]],
                    'drill' => ['values' => [['code' => 'no_drilling']]],
                ],
            ],
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            ['sizes', 'corners', 'texture', 'special_finish'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['standard', 'square', 'custom'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($options, 'option_groups.0.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($options, 'option_groups.0.values.1.swatch_image'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($options, 'option_groups.0.values.2.description'),
        );
        $this->assertSame(
            ['water_ripple_paper', 'linen_paper', 'eggshell_paper', 'white_cardstock', 'pearlized_paper', 'pin_point_embossed_paper'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame('water_ripple_paper', data_get($options, 'option_groups.2.default'));
        $this->assertArrayNotHasKey('paper_finish', $options);
        $this->assertArrayNotHasKey('print_code', $options);
        $this->assertArrayNotHasKey('drill', $options);
        $this->assertArrayNotHasKey('special_finish_on_sides', $options);
    }

    public function test_legacy_product_options_are_normalized_for_the_storefront(): void
    {
        $product = new Product([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_options' => json_decode(
                file_get_contents(base_path('content/product-options/business-cards/classic-special-business-cards.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            ['standard', 'square', 'custom'],
            array_map(
                fn (array $value): string => $value['code'],
                data_get($options, 'sizes', []),
            ),
        );
        $this->assertArrayNotHasKey('paper_finish', $options);
        $this->assertSame(
            ['water_ripple_paper', 'linen_paper', 'eggshell_paper', 'white_cardstock', 'pearlized_paper', 'pin_point_embossed_paper'],
            array_map(
                fn (array $value): string => $value['code'],
                data_get($options, 'texture', []),
            ),
        );
        $this->assertArrayNotHasKey('print_code', $options);
        $this->assertArrayNotHasKey('drill', $options);
        $this->assertArrayNotHasKey('special_finish_on_sides', $options);
    }
}
