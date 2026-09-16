<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardProductRenameMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_renames_preserve_data_and_assign_new_navigation_categories(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $renames = [
            'super-business-cards' => [
                'slug' => 'super-standard-business-cards',
                'name' => 'Super Standard Business Cards',
                'category' => 'super-business-cards',
            ],
            'luxe-business-cards' => [
                'slug' => 'super-luxe-business-cards',
                'name' => 'Super Luxe Business Cards',
                'category' => 'super-business-cards',
            ],
            'classic-quality-business-cards' => [
                'slug' => 'standard-quality-business-cards',
                'name' => 'Standard Quality Business Cards',
                'category' => 'quality-business-cards',
            ],
            'classic-solid-business-cards' => [
                'slug' => 'solid-quality-business-cards',
                'name' => 'Solid Quality Business Cards',
                'category' => 'quality-business-cards',
            ],
        ];

        $originalIds = [];

        foreach ($renames as $oldSlug => $rename) {
            $product = Product::create([
                'name' => ucwords(str_replace('-', ' ', $oldSlug)),
                'slug' => $oldSlug,
                'description' => 'Keep this description',
                'price_line' => '50 cards from $99',
                'featured_image' => '/images/keep-this-image.png',
                'product_category_id' => $businessCards->id,
                'product_options' => ['keep' => 'these options'],
                'product_config' => [
                    'product' => [
                        'name' => ucwords(str_replace('-', ' ', $oldSlug)),
                        'slug' => $oldSlug,
                    ],
                    'media' => [
                        'gallery' => ['/images/keep-this-image.png'],
                    ],
                    'detail_sections' => [
                        'paper_stocks' => [
                            'items' => [
                                [
                                    'name' => 'Super Business Cards',
                                    'href' => '/business-cards/super',
                                    'cta' => 'Shop Super Business Cards →',
                                ],
                                [
                                    'name' => 'Luxe Business Cards',
                                    'href' => '/business-cards/luxe',
                                    'cta' => 'Shop Luxe Business Cards →',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $originalIds[$oldSlug] = $product->id;
        }

        $migration = require base_path(
            'database/migrations/2026_09_16_000001_rename_business_card_product_catalog.php',
        );
        $migration->up();
        $migration->up();

        foreach ($renames as $oldSlug => $rename) {
            $product = Product::where('slug', $rename['slug'])->firstOrFail();

            $this->assertSame($originalIds[$oldSlug], $product->id);
            $this->assertSame($rename['name'], $product->name);
            $this->assertSame('50 cards from $99', $product->price_line);
            $this->assertSame(['keep' => 'these options'], $product->product_options);
            $this->assertSame($rename['name'], data_get($product->product_config, 'product.name'));
            $this->assertSame($rename['slug'], data_get($product->product_config, 'product.slug'));
            $this->assertSame('/images/keep-this-image.png', $product->featured_image);
            $this->assertSame(
                ['/images/keep-this-image.png'],
                data_get($product->product_config, 'media.gallery'),
            );
            $this->assertSame(
                'Super Standard Business Cards',
                data_get($product->product_config, 'detail_sections.paper_stocks.items.0.name'),
            );
            $this->assertSame(
                '/business-cards/super-standard',
                data_get($product->product_config, 'detail_sections.paper_stocks.items.0.href'),
            );
            $this->assertSame(
                ProductCategory::where('slug', $rename['category'])->value('id'),
                $product->product_category_id,
            );
            $this->assertDatabaseMissing('products', ['slug' => $oldSlug]);
        }

        $this->assertSame(1, Product::where('slug', 'super-standard-business-cards')->count());
        $this->assertSame(1, Product::where('slug', 'super-luxe-business-cards')->count());
        $this->assertSame(1, Product::where('slug', 'standard-quality-business-cards')->count());
        $this->assertSame(1, Product::where('slug', 'solid-quality-business-cards')->count());
        $this->assertSame($businessCards->id, ProductCategory::where('slug', 'super-business-cards')->value('parent_id'));
        $this->assertSame($businessCards->id, ProductCategory::where('slug', 'quality-business-cards')->value('parent_id'));
    }
}
