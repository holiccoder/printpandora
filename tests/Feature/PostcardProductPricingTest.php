<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use App\Support\PostcardProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostcardProductPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_postcard_routes_render_the_new_product_and_canonicalize_internal_slugs(): void
    {
        $product = $this->makePostcard();

        $this->get('/postcards/classic-standard')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/show')
                ->where('product.slug', $product->slug)
                ->where('productOptions.pricing_rules.0.pricing.area_based', true)
                ->where('productOptions.galleries.0.images', PostcardProductCatalog::gallery()));

        $this->get('/classic-standard-postcards')
            ->assertStatus(301)
            ->assertRedirect('/postcards/classic-standard');
    }

    public function test_postcard_pricing_uses_area_and_adds_process_costs(): void
    {
        $product = $this->makePostcard();
        $pricing = app(PricingService::class);
        $baseOptions = [
            'sizes' => 'standard',
            'corners' => 'square',
            'quantity' => '200',
        ];

        $this->assertSame(
            13.0,
            $pricing->calculate($product->id, $baseOptions),
        );
        $this->assertSame(
            33.0,
            $pricing->calculate($product->id, $baseOptions + [
                'uv_finish' => 'single_side_uv',
            ]),
        );
        $this->assertSame(
            54.0,
            $pricing->calculate($product->id, $baseOptions + [
                'uv_finish' => 'both_sides_uv',
            ]),
        );
        $this->assertSame(
            60.0,
            $pricing->calculate($product->id, $baseOptions + [
                'special_finish' => ['custom_die_cut'],
            ]),
        );
    }

    private function makePostcard(): Product
    {
        $category = ProductCategory::query()->firstOrCreate(
            ['slug' => 'cards-and-postcards'],
            ['name' => 'Cards & Postcards'],
        );
        $gallery = PostcardProductCatalog::gallery();

        return Product::query()->create([
            'name' => 'Classic Standard Postcards',
            'slug' => 'classic-standard-postcards',
            'description' => '<p>Postcards.</p>',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
            'featured_image' => $gallery[0],
            'product_config' => [
                'schema_version' => 1,
                'product' => [
                    'slug' => 'classic-standard-postcards',
                    'name' => 'Classic Standard Postcards',
                    'featured_image' => $gallery[0],
                ],
                'options' => [
                    'sizes' => [
                        'label' => 'Size',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'standard',
                        'values' => [
                            [
                                'code' => 'standard',
                                'label' => 'Standard',
                                'width' => '2.0',
                                'height' => '3.5',
                                'area_sq_m' => 0.00486,
                            ],
                            [
                                'code' => 'square',
                                'label' => 'Square',
                                'width' => '2.5',
                                'height' => '2.5',
                                'area_sq_m' => 0.00433929,
                            ],
                        ],
                    ],
                    'corners' => [
                        'label' => 'Corners',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'square',
                        'values' => [
                            ['code' => 'square', 'label' => 'Square'],
                            ['code' => 'rounded', 'label' => 'Rounded'],
                        ],
                    ],
                    'uv_finish' => [
                        'label' => 'UV Finish',
                        'type' => 'select',
                        'required' => false,
                        'default' => null,
                        'values' => [
                            ['code' => 'single_side_uv', 'label' => 'Single side UV'],
                            ['code' => 'both_sides_uv', 'label' => 'Both sides UV'],
                        ],
                    ],
                    'special_finish' => [
                        'label' => 'Special Finish',
                        'type' => 'multi_select',
                        'required' => false,
                        'default' => [],
                        'values' => [
                            ['code' => 'custom_die_cut', 'label' => 'Custom Die-Cut'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery' => $gallery,
                    'gallery_rules' => [[
                        'id' => 'default',
                        'match' => [],
                        'images' => $gallery,
                        'primary' => $gallery[0],
                    ]],
                ],
                'pricing' => PostcardProductCatalog::pricingFor('classic-standard-postcards'),
                'faq' => [],
                'detail_sections' => [],
            ],
        ]);
    }
}
