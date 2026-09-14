<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardNfcRemovalMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_migration_removes_nfc_from_canonical_and_legacy_shapes_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Premium Metal Business Cards',
            'slug' => 'premium-metal-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => [
                    'subtitle' => 'Premium metal business cards with engraving, color, plating, or NFC options.',
                ],
                'options' => [
                    'special_finish' => [
                        'label' => 'Special Finish',
                        'default' => 'nfc',
                        'values' => [
                            ['code' => 'nfc', 'label' => 'NFC'],
                            ['code' => 'laser_engraving', 'label' => 'Laser Engraving'],
                            ['code' => 'plating', 'label' => 'Plating'],
                        ],
                    ],
                    'with_nfc' => [
                        'default' => 'no_nfc',
                        'values' => [
                            ['code' => 'no_nfc', 'label' => 'No NFC'],
                            ['code' => 'with_nfc', 'label' => 'With NFC'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [[
                        'id' => 'nfc',
                        'match' => ['special_finish' => 'nfc'],
                        'images' => ['/images/nfc.png'],
                    ]],
                ],
                'pricing' => [
                    'scenarios' => [
                        'rectangle' => [
                            'processes' => [
                                ['code' => 'nfc', 'label' => 'NFC'],
                                ['code' => 'foil', 'label' => 'Foil'],
                            ],
                        ],
                    ],
                    'rules' => [[
                        'id' => 'pricing-rectangle',
                        'pricing' => [
                            'processes' => [
                                ['code' => 'nfc', 'name' => 'NFC'],
                                ['code' => 'foil', 'name' => 'Foil'],
                            ],
                        ],
                    ]],
                ],
            ],
        ]);

        Product::create([
            'name' => 'Classic Cotton Business Card',
            'slug' => 'classic-cotton-business-card',
            'product_category_id' => $category->id,
            'product_options' => [
                'with_nfc' => [
                    ['code' => 'no_nfc', 'name' => 'No NFC'],
                    ['code' => 'with_nfc', 'name' => 'With NFC'],
                ],
                'special_finish' => [
                    ['code' => 'nfc', 'name' => 'NFC'],
                    ['code' => 'laser', 'name' => 'Laser'],
                ],
                'pricing_data' => [
                    'rectangle' => [
                        'processes' => [
                            ['code' => 'nfc', 'label' => 'NFC'],
                            ['code' => 'foil', 'label' => 'Foil'],
                        ],
                    ],
                ],
            ],
        ]);

        Product::create([
            'name' => 'Luxe Metal Business Cards',
            'slug' => 'luxe-metal-business-cards',
            'description' => '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, plating, and NFC options.</p>',
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => [
                    'description' => '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, plating, and NFC options.</p>',
                ],
            ],
        ]);

        Product::create([
            'name' => 'Premium PVC Card',
            'slug' => 'premium-pvc-card',
            'subtitle' => '0.03 inches premium PVC NFC cards with optional print code.',
            'description' => '<p>Premium PVC NFC cards at 0.03 inches combine durable construction with optional print-code functionality.</p>',
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => [
                    'subtitle' => '0.03 inches premium PVC NFC cards with optional print code.',
                    'description' => '<p>Premium PVC NFC cards at 0.03 inches combine durable construction with optional print-code functionality.</p>',
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000010_remove_business_card_nfc_options.php',
        );
        $migration->up();

        $firstPass = Product::query()
            ->whereIn('slug', [
                'premium-metal-business-cards',
                'classic-cotton-business-card',
                'luxe-metal-business-cards',
                'premium-pvc-card',
            ])
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->slug => [
                    'subtitle' => $product->subtitle,
                    'description' => $product->description,
                    'product_config' => $product->product_config,
                    'product_options' => $product->product_options,
                ],
            ])
            ->all();

        $migration->up();

        $secondPass = Product::query()
            ->whereIn('slug', array_keys($firstPass))
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->slug => [
                    'subtitle' => $product->subtitle,
                    'description' => $product->description,
                    'product_config' => $product->product_config,
                    'product_options' => $product->product_options,
                ],
            ])
            ->all();

        $this->assertSame($firstPass, $secondPass);

        $metal = Product::where('slug', 'premium-metal-business-cards')->firstOrFail();
        $this->assertArrayNotHasKey('with_nfc', $metal->product_config['options']);
        $this->assertSame(
            ['laser_engraving', 'plating'],
            array_column($metal->product_config['options']['special_finish']['values'], 'code'),
        );
        $this->assertSame(
            'laser_engraving',
            $metal->product_config['options']['special_finish']['default'],
        );
        $this->assertSame(
            ['foil'],
            array_column($metal->product_config['pricing']['scenarios']['rectangle']['processes'], 'code'),
        );
        $this->assertSame(
            ['foil'],
            array_column($metal->product_config['pricing']['rules'][0]['pricing']['processes'], 'code'),
        );
        $this->assertSame([], $metal->product_config['media']['gallery_rules']);
        $this->assertStringNotContainsString('NFC', $metal->product_config['product']['subtitle']);

        $luxe = Product::where('slug', 'luxe-metal-business-cards')->firstOrFail();
        $this->assertSame(
            '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, and plating options.</p>',
            $luxe->description,
        );
        $this->assertSame(
            '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, and plating options.</p>',
            $luxe->product_config['product']['description'],
        );

        $cotton = Product::where('slug', 'classic-cotton-business-card')->firstOrFail();
        $this->assertArrayNotHasKey('with_nfc', $cotton->product_options);
        $this->assertSame(
            ['laser'],
            array_column($cotton->product_options['special_finish'], 'code'),
        );
        $this->assertSame(
            ['foil'],
            array_column($cotton->product_options['pricing_data']['rectangle']['processes'], 'code'),
        );

        $pvc = Product::where('slug', 'premium-pvc-card')->firstOrFail();
        $this->assertSame('0.03 inches premium PVC cards with optional print code.', $pvc->subtitle);
        $this->assertSame(
            '<p>Premium PVC cards at 0.03 inches combine durable construction with optional print-code functionality.</p>',
            $pvc->description,
        );
        $this->assertSame(
            '0.03 inches premium PVC cards with optional print code.',
            $pvc->product_config['product']['subtitle'],
        );
        $this->assertSame(
            '<p>Premium PVC cards at 0.03 inches combine durable construction with optional print-code functionality.</p>',
            $pvc->product_config['product']['description'],
        );
    }

    public function test_storefront_normalization_hides_stale_nfc_from_canonical_and_legacy_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $canonical = Product::create([
            'name' => 'Premium Metal Business Cards',
            'slug' => 'premium-metal-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'special_finish' => [
                        'default' => 'nfc',
                        'values' => [
                            ['code' => 'nfc', 'label' => 'NFC'],
                            ['code' => 'laser_engraving', 'label' => 'Laser Engraving'],
                        ],
                    ],
                    'with_nfc' => [
                        'default' => 'with_nfc',
                        'values' => [['code' => 'with_nfc', 'label' => 'With NFC']],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'scenarios' => [
                        'rectangle' => [
                            'base_price_per_card' => 1,
                            'start_quantity' => 50,
                            'processes' => [
                                ['code' => 'nfc', 'label' => 'NFC'],
                                ['code' => 'foil', 'label' => 'Foil'],
                            ],
                        ],
                    ],
                    'rules' => [[
                        'pricing' => [
                            'processes' => [
                                ['code' => 'nfc', 'name' => 'NFC'],
                                ['code' => 'foil', 'name' => 'Foil'],
                            ],
                        ],
                    ]],
                ],
            ],
        ]);

        $legacy = Product::create([
            'name' => 'Classic Cotton Business Card',
            'slug' => 'classic-cotton-business-card',
            'product_category_id' => $category->id,
            'product_options' => [
                'with_nfc' => [['code' => 'with_nfc', 'name' => 'With NFC']],
                'corners' => [['code' => 'square', 'name' => 'Square']],
                'pricing_data' => [
                    'rectangle' => [
                        'base_price' => 1,
                        'start_quantity' => 50,
                        'processes' => [
                            ['code' => 'nfc', 'label' => 'NFC'],
                            ['code' => 'foil', 'label' => 'Foil'],
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(ProductConfigurationService::class);

        foreach ([$canonical, $legacy] as $product) {
            $options = $service->storefrontOptions($product->fresh());

            $this->assertNotContains('with_nfc', data_get($options, 'option_groups.*.key'));
            $this->assertNotContains('nfc', data_get($options, 'option_groups.*.values.*.code'));
            $this->assertNotContains('NFC', data_get($options, 'option_groups.*.values.*.name'));
            $this->assertNotContains('nfc', data_get($options, 'pricing_data.rectangle.processes.*.code'));
            $this->assertNotContains('nfc', data_get($options, 'pricing_rules.*.pricing.processes.*.code'));
        }
    }

    public function test_all_purchasable_business_card_storefront_payloads_exclude_nfc(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $slugs = [
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card',
            'super-business-cards',
            'luxe-business-cards',
            'basic-pvc-card',
            'standard-pvc-card',
            'premium-pvc-card',
            'classic-metal-business-cards',
            'premium-metal-business-cards',
            'luxe-metal-business-cards',
            'classic-standard-business-cards',
            'classic-special-business-cards',
            'classic-quality-business-cards',
            'classic-solid-business-cards',
        ];

        foreach ($slugs as $slug) {
            Product::create([
                'name' => ucwords(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'product_config' => [
                    'options' => [
                        'special_finish' => [
                            'default' => 'nfc',
                            'values' => [
                                ['code' => 'nfc', 'label' => 'NFC'],
                                ['code' => 'laser_engraving', 'label' => 'Laser Engraving'],
                            ],
                        ],
                        'with_nfc' => [
                            'default' => 'with_nfc',
                            'values' => [['code' => 'with_nfc', 'label' => 'With NFC']],
                        ],
                    ],
                    'pricing' => [
                        'mode' => 'rule_based',
                        'scenarios' => [
                            'rectangle' => [
                                'base_price_per_card' => 1,
                                'start_quantity' => 50,
                                'processes' => [
                                    ['code' => 'nfc', 'label' => 'NFC'],
                                    ['code' => 'foil', 'label' => 'Foil'],
                                ],
                            ],
                        ],
                        'rules' => [[
                            'pricing' => [
                                'processes' => [['code' => 'nfc', 'name' => 'NFC']],
                            ],
                        ]],
                    ],
                ],
            ]);
        }

        $service = app(ProductConfigurationService::class);

        foreach ($slugs as $slug) {
            $payload = $service->storefrontOptions(Product::where('slug', $slug)->firstOrFail());

            $this->assertIsArray($payload);
            $this->assertStringNotContainsString('nfc', strtolower(json_encode($payload, JSON_THROW_ON_ERROR)));
        }
    }
}
