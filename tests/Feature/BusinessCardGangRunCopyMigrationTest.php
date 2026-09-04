<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardGangRunCopyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_updates_only_the_product_specific_gang_run_card(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Keep this section'],
                    'feature_cards' => [
                        ['title' => 'Keep this title'],
                        [
                            'title' => 'Keep this gang-run title',
                            'description' => 'Keep this description',
                            'tooltip_content' => 'Replace this copy',
                        ],
                    ],
                ],
            ],
        ]);

        Product::create([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [],
            'product_options' => [
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Keep this legacy section'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_04_000001_set_business_card_gang_run_copy.php',
        );
        $migration->up();

        $standard = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();
        $special = Product::where('slug', 'classic-special-business-cards')->firstOrFail();

        $this->assertSame(
            'Keep this section',
            data_get($standard->product_config, 'detail_sections.design_specifications.heading'),
        );
        $this->assertSame(
            'Keep this title',
            data_get($standard->product_config, 'detail_sections.feature_cards.0.title'),
        );
        $this->assertSame(
            'Keep this gang-run title',
            data_get($standard->product_config, 'detail_sections.feature_cards.1.title'),
        );
        $this->assertSame(
            'Keep this description',
            data_get($standard->product_config, 'detail_sections.feature_cards.1.description'),
        );
        $this->assertStringContainsString(
            'color reproduction is relatively less accurate',
            strtolower((string) data_get($standard->product_config, 'detail_sections.feature_cards.1.tooltip_content')),
        );
        $this->assertSame(
            'Keep this legacy section',
            data_get($special->product_options, 'detail_sections.design_specifications.heading'),
        );
        $this->assertStringContainsString(
            'as accurately as dedicated printing',
            (string) data_get($special->product_options, 'detail_sections.feature_cards.1.tooltip_content'),
        );
    }
}
