<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignServicePricingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The products table has no price column (static fallback resolves to
     * 0.0), so the design fee is the only component of these totals.
     */
    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Widgets',
            'slug' => 'widgets-'.uniqid(),
        ]);

        return Product::create([
            'name' => 'Test Card',
            'slug' => 'test-card-'.uniqid(),
            'product_category_id' => $category->id,
        ]);
    }

    public function test_price_unchanged_without_design_service(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(
            0.0,
            app(PricingService::class)->calculate($product->id, []),
        );
    }

    public function test_card_layout_code_adds_29(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(
            29.0,
            app(PricingService::class)->calculate($product->id, [
                'design_service' => 'card_layout',
            ]),
        );
    }

    public function test_card_design_code_adds_79(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(
            79.0,
            app(PricingService::class)->calculate($product->id, [
                'design_service' => 'card_design',
            ]),
        );
    }

    public function test_invalid_design_service_code_adds_nothing(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(
            0.0,
            app(PricingService::class)->calculate($product->id, [
                'design_service' => 'hacker_special',
            ]),
        );
    }

    public function test_condition_based_pricing_json_uses_the_matching_option_rule(): void
    {
        $product = $this->makeProduct();
        $product->forceFill([
            'product_config' => [
                'options' => [
                    'paper_finish' => [
                        'label' => 'Paper Finish',
                        'values' => [
                            ['code' => 'matte', 'label' => 'Matte'],
                            ['code' => 'gloss', 'label' => 'Gloss'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'rules' => [
                        [
                            'id' => 'matte',
                            'match' => ['paper_finish' => 'matte'],
                            'pricing' => [
                                'packageName' => 'Matte',
                                'basePrice' => 0.14,
                                'startQuantity' => 200,
                                'paperRates' => ['200' => 0, '500' => 20],
                                'processes' => [],
                            ],
                        ],
                        [
                            'id' => 'gloss',
                            'match' => ['paper_finish' => 'gloss'],
                            'pricing' => [
                                'packageName' => 'Gloss',
                                'basePrice' => 0.2,
                                'startQuantity' => 200,
                                'paperRates' => ['200' => 0, '500' => 0],
                                'processes' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->save();

        $this->assertSame(
            56.0,
            app(PricingService::class)->calculate($product->id, [
                'paper_finish' => 'matte',
                'quantity' => '500',
            ]),
        );
        $this->assertSame(
            100.0,
            app(PricingService::class)->calculate($product->id, [
                'paper_finish' => 'gloss',
                'quantity' => '500',
            ]),
        );
    }

    public function test_condition_based_pricing_accepts_a_multi_select_option(): void
    {
        $product = $this->makeProduct();
        $product->forceFill([
            'product_config' => [
                'options' => [
                    'processes' => [
                        'label' => 'Processes',
                        'type' => 'multi_select',
                        'values' => [
                            ['code' => 'foil', 'label' => 'Foil'],
                            ['code' => 'embossing', 'label' => 'Embossing'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'rules' => [
                        [
                            'id' => 'foil',
                            'match' => ['processes' => 'foil'],
                            'pricing' => [
                                'packageName' => 'Foil',
                                'basePrice' => 0.14,
                                'startQuantity' => 200,
                                'paperRates' => ['200' => 0],
                                'processes' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->save();

        $this->assertSame(
            28.0,
            app(PricingService::class)->calculate($product->id, [
                'processes' => ['embossing', 'foil'],
                'quantity' => '200',
            ]),
        );
    }

    public function test_request_stores_code_and_fee_snapshot(): void
    {
        $this->post(route('business-card-design-service.store'), [
            'email' => 'client@example.com',
            'business_name' => 'Acme',
            'business_card_type' => 'Classic Standard Business Cards',
            'design_service_code' => 'card_design',
            'terms_accepted' => true,
        ])->assertRedirect(route('business-card-design-service'));

        $this->assertDatabaseHas('design_service_requests', [
            'email' => 'client@example.com',
            'design_service_code' => 'card_design',
            'design_service_fee' => 79.00,
        ]);
    }

    public function test_invalid_code_is_rejected_and_not_stored(): void
    {
        $this->post(route('business-card-design-service.store'), [
            'email' => 'client@example.com',
            'business_name' => 'Acme',
            'business_card_type' => 'Classic Standard Business Cards',
            'design_service_code' => 'hacker_special',
            'terms_accepted' => true,
        ])->assertSessionHasErrors('design_service_code');

        $this->assertDatabaseCount('design_service_requests', 0);
    }

    public function test_return_to_redirects_back_to_product_page(): void
    {
        $this->post(route('business-card-design-service.store'), [
            'email' => 'client@example.com',
            'business_name' => 'Acme',
            'business_card_type' => 'Classic Standard Business Cards',
            'design_service_code' => 'card_layout',
            'return_to' => '/classic-standard-business-cards',
            'terms_accepted' => true,
        ])->assertRedirect('/classic-standard-business-cards');
    }

    public function test_external_return_to_is_ignored(): void
    {
        $this->post(route('business-card-design-service.store'), [
            'email' => 'client@example.com',
            'business_name' => 'Acme',
            'business_card_type' => 'Classic Standard Business Cards',
            'return_to' => 'https://evil.example.com',
            'terms_accepted' => true,
        ])->assertRedirect(route('business-card-design-service'));
    }
}
