<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Database\Seeders\BusinessCardProductOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardProductOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requested_contracts_are_applied_and_existing_content_is_preserved(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $pvc = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
            'parent_id' => $businessCards->id,
        ]);

        $quality = Product::create([
            'name' => 'Classic Quality Business Cards',
            'slug' => 'classic-quality-business-cards',
            'product_category_id' => $businessCards->id,
            'product_config' => [
                'options' => [
                    'sizes' => ['values' => [['code' => 'standard'], ['code' => 'square']]],
                    'paper_finish' => ['values' => [['code' => 'matte']]],
                ],
                'media' => ['gallery' => ['/images/quality.jpg']],
                'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                'detail_sections' => ['design_specifications' => ['heading' => 'Keep this design spec']],
            ],
        ]);

        foreach (['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'] as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'product_category_id' => $pvc->id,
                'product_config' => [],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        $quality->refresh();

        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($quality->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            ['shattered_glass_film', 'holographic_film', 'starlight_film', 'holographic_star_film', 'soft_touch_film'],
            data_get($quality->product_config, 'options.texture.values.*.code'),
        );
        $this->assertContains(
            'cold_bright_gold',
            data_get($quality->product_config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame(['/images/quality.jpg'], data_get($quality->product_config, 'media.gallery'));
        $this->assertSame('Keep this FAQ', data_get($quality->product_config, 'faq.0.question'));
        $this->assertSame('Keep this design spec', data_get($quality->product_config, 'detail_sections.design_specifications.heading'));

        $this->assertSame(
            ['matte', 'gloss', 'frosted'],
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.paper_finish.values.*.code'),
        );
        $this->assertSame(
            ['no_print_code_or_signature_stripe', 'print_code', 'signature_stripe'],
            data_get(Product::where('slug', 'standard-pvc-card')->firstOrFail()->product_config, 'options.print_code_or_signature_stripe.values.*.code'),
        );
        $this->assertSame(
            ['no_print_code', 'print_code'],
            data_get(Product::where('slug', 'premium-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.*.code'),
        );
    }

    public function test_missing_metal_products_are_created_under_business_cards(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        (new BusinessCardProductOptionsSeeder)->run();

        $metal = ProductCategory::where('slug', 'metal-business-cards')->firstOrFail();

        $this->assertSame($businessCards->id, $metal->parent_id);
        $this->assertSame(
            ['classic-metal-business-cards', 'luxe-metal-business-cards', 'premium-metal-business-cards'],
            Product::where('product_category_id', $metal->id)->orderBy('slug')->pluck('slug')->all(),
        );
        $this->assertSame(
            ['0_3_mm', '0_5_mm'],
            data_get(Product::where('slug', 'classic-metal-business-cards')->firstOrFail()->product_config, 'options.thickness.values.*.code'),
        );
        $this->assertSame(
            ['laser_engraving', 'color_printing', 'plating', 'nfc'],
            data_get(Product::where('slug', 'premium-metal-business-cards')->firstOrFail()->product_config, 'options.special_finish.values.*.code'),
        );
    }

    public function test_legacy_products_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/pvc-business-cards/basic-pvc-card.json'));

        if ($contents === false) {
            $this->fail('The Basic PVC legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            ['matte', 'gloss', 'frosted'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['no_print_code', 'print_code'],
            array_column(data_get($options, 'option_groups.1.values', []), 'code'),
        );
    }

    public function test_all_cotton_business_cards_receive_the_shared_contract_and_imported_gallery(): void
    {
        $cotton = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        $slugs = [
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card',
        ];

        foreach ($slugs as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'product_category_id' => $cotton->id,
                'product_config' => [
                    'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                    'detail_sections' => [
                        'design_specifications' => ['heading' => 'Keep this design spec'],
                    ],
                ],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        foreach ($slugs as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $options = data_get($product->product_config, 'options');
            $shortSlug = str_replace('-cotton-business-card', '', $slug);
            $gallery = [
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-01.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-02.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-03.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-04.png",
            ];

            $this->assertSame(['corners', 'with_nfc'], array_keys($options));
            $this->assertSame(['square', 'rounded'], data_get($options, 'corners.values.*.code'));
            $this->assertSame(['with_nfc', 'no_nfc'], data_get($options, 'with_nfc.values.*.code'));
            $this->assertSame($gallery, data_get($product->product_config, 'media.gallery'));
            $this->assertSame($gallery[0], $product->featured_image);
            $this->assertSame('Keep this FAQ', data_get($product->product_config, 'faq.0.question'));
            $this->assertSame(
                'Keep this design spec',
                data_get($product->product_config, 'detail_sections.design_specifications.heading'),
            );
        }
    }

    public function test_legacy_cotton_products_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/cotton-business-cards/basic-cotton-business-card.json'));

        if ($contents === false) {
            $this->fail('The Basic cotton business card legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            ['corners', 'with_nfc'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['square', 'rounded'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['with_nfc', 'no_nfc'],
            array_column(data_get($options, 'option_groups.1.values', []), 'code'),
        );
    }
}
