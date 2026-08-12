<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
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

        $product->refresh();

        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($product->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            ['matte', 'gloss', 'uv'],
            data_get($product->product_config, 'options.paper_finish.values.*.code'),
        );
        $this->assertSame(
            ['square', 'rounded'],
            data_get($product->product_config, 'options.corners.values.*.code'),
        );
        $this->assertSame(
            [
                'no_special_finish',
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
            ['one_side', 'both_sides'],
            data_get($product->product_config, 'options.special_finish_on_sides.values.*.code'),
        );
        $this->assertSame(
            [
                'pin_hole_paper',
                'water_ripple_paper',
                'linen_paper',
                'eggshell_paper',
                'white_cardstock',
                'pearlized_paper',
            ],
            data_get($product->product_config, 'options.texture.values.*.code'),
        );
        $this->assertArrayNotHasKey('print_code', $product->product_config['options']);
        $this->assertArrayNotHasKey('drill', $product->product_config['options']);
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
            ['standard', 'square', 'custom'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['matte', 'gloss', 'uv'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame(
            ['pin_hole_paper', 'water_ripple_paper', 'linen_paper', 'eggshell_paper', 'white_cardstock', 'pearlized_paper'],
            array_column(data_get($options, 'option_groups.5.values', []), 'code'),
        );
        $this->assertArrayNotHasKey('print_code', $options);
        $this->assertArrayNotHasKey('drill', $options);
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
        $this->assertSame('3D UV', data_get($options, 'paper_finish.2.name'));
        $this->assertSame(
            ['pin_hole_paper', 'water_ripple_paper', 'linen_paper', 'eggshell_paper', 'white_cardstock', 'pearlized_paper'],
            array_map(
                fn (array $value): string => $value['code'],
                data_get($options, 'texture', []),
            ),
        );
        $this->assertArrayNotHasKey('print_code', $options);
        $this->assertArrayNotHasKey('drill', $options);
    }
}
