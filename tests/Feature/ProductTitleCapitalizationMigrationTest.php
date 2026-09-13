<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTitleCapitalizationMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private const TITLES = [
        'basic-cotton-business-card' => 'Basic Cotton Business Card',
        'classic-cotton-business-card' => 'Classic Cotton Business Card',
        'premium-cotton-business-card' => 'Premium Cotton Business Card',
        'luxe-cotton-business-card' => 'Luxe Cotton Business Card',
        'grand-cotton-business-card' => 'Grand Cotton Business Card',
        'standard-pvc-card' => 'Standard PVC Card',
        'premium-pvc-card' => 'Premium PVC Card',
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_TITLES = [
        'basic-cotton-business-card' => 'Basic cotton business card',
        'classic-cotton-business-card' => 'Classic cotton business card',
        'premium-cotton-business-card' => 'Premium cotton business card',
        'luxe-cotton-business-card' => 'Luxe cotton business card',
        'grand-cotton-business-card' => 'Grand cotton business card',
        'standard-pvc-card' => 'Standard PVC card',
        'premium-pvc-card' => 'Premium PVC card',
    ];

    public function test_migration_capitalizes_all_affected_titles_and_nested_config_names(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach (self::LEGACY_TITLES as $slug => $legacyTitle) {
            Product::create([
                'name' => $legacyTitle,
                'slug' => $slug,
                'product_category_id' => $category->getKey(),
                'is_active' => true,
                'product_config' => [
                    'product' => [
                        'name' => $legacyTitle,
                        'subtitle' => 'Keep this subtitle',
                    ],
                    'options' => [
                        'sizes' => [
                            'values' => [['code' => 'standard']],
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_13_000001_capitalize_product_titles.php',
        );
        $migration->up();

        $afterFirstRun = Product::query()
            ->orderBy('slug')
            ->get()
            ->map(static fn (Product $product): array => $product->getAttributes())
            ->all();

        $migration->up();

        $this->assertSame(
            $afterFirstRun,
            Product::query()
                ->orderBy('slug')
                ->get()
                ->map(static fn (Product $product): array => $product->getAttributes())
                ->all(),
        );

        foreach (self::TITLES as $slug => $title) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();

            $this->assertSame($title, $product->name);
            $this->assertSame($title, data_get($product->product_config, 'product.name'));
            $this->assertSame('Keep this subtitle', data_get($product->product_config, 'product.subtitle'));
            $this->assertSame(['standard'], data_get($product->product_config, 'options.sizes.values.*.code'));
            $this->assertSame($slug, $product->slug);
        }
    }

    public function test_migration_updates_legacy_product_without_creating_missing_config(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $product = Product::create([
            'name' => self::LEGACY_TITLES['standard-pvc-card'],
            'slug' => 'standard-pvc-card',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_13_000001_capitalize_product_titles.php',
        );
        $migration->up();

        $product->refresh();

        $this->assertSame(self::TITLES['standard-pvc-card'], $product->name);
        $this->assertNull($product->product_config);
    }

    public function test_migration_down_restores_both_title_copies(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);
        $product = Product::create([
            'name' => self::LEGACY_TITLES['basic-cotton-business-card'],
            'slug' => 'basic-cotton-business-card',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
            'product_config' => [
                'product' => [
                    'name' => self::LEGACY_TITLES['basic-cotton-business-card'],
                    'subtitle' => 'Keep this subtitle',
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_13_000001_capitalize_product_titles.php',
        );
        $migration->up();
        $migration->down();

        $product->refresh();

        $this->assertSame(self::LEGACY_TITLES['basic-cotton-business-card'], $product->name);
        $this->assertSame(
            self::LEGACY_TITLES['basic-cotton-business-card'],
            data_get($product->product_config, 'product.name'),
        );
        $this->assertSame('Keep this subtitle', data_get($product->product_config, 'product.subtitle'));
    }
}
