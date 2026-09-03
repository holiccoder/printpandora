<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogSynchronizationCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $sourceDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDirectory = storage_path('framework/testing/catalog-sync-'.Str::uuid());
        File::ensureDirectoryExists($this->sourceDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceDirectory);

        parent::tearDown();
    }

    public function test_dry_run_with_prune_previews_without_mutating_the_database(): void
    {
        ProductCategory::create([
            'name' => 'Remote Root',
            'slug' => 'catalog-root',
        ]);
        ProductCategory::create([
            'name' => 'Remote Only',
            'slug' => 'remote-only',
        ]);

        $this->writeSnapshot(
            categories: [
                $this->categoryRow(100, 'Source Root', 'catalog-root'),
                $this->categoryRow(200, 'Source Child', 'catalog-child', 100),
            ],
            products: [
                $this->productRow(500, 'Source Product', 'source-product', 200),
            ],
        );

        $this->artisan('catalog:sync', [
            '--source' => $this->sourceDirectory,
            '--dry-run' => true,
            '--prune' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Remote Root',
            'slug' => 'catalog-root',
        ]);
        $this->assertDatabaseHas('product_categories', ['slug' => 'remote-only']);
        $this->assertDatabaseMissing('product_categories', ['slug' => 'catalog-child']);
        $this->assertDatabaseMissing('products', ['slug' => 'source-product']);
    }

    public function test_sync_upserts_by_slug_and_maps_relationships_without_using_remote_ids(): void
    {
        ProductCategory::create([
            'name' => 'ID Offset',
            'slug' => 'id-offset',
        ]);
        $root = ProductCategory::create([
            'name' => 'Old Root Name',
            'slug' => 'catalog-root',
        ]);
        $remoteOnly = ProductCategory::create([
            'name' => 'Remote Only',
            'slug' => 'remote-only',
        ]);
        $existingProduct = Product::create([
            'name' => 'Old Product Name',
            'slug' => 'existing-product',
            'product_category_id' => $root->getKey(),
            'is_active' => false,
            'product_config' => ['version' => 'old'],
        ]);

        $this->writeSnapshot(
            categories: [
                $this->categoryRow(100, 'Catalog Root', 'catalog-root'),
                $this->categoryRow(200, 'Catalog Child', 'catalog-child', 100),
            ],
            products: [
                $this->productRow(500, 'Updated Product', 'existing-product', 200, [
                    'subtitle' => '<p>Updated subtitle</p>',
                    'weight' => 275,
                    'is_active' => 1,
                    'bullet_points' => json_encode(['First', 'Second'], JSON_THROW_ON_ERROR),
                    'product_config' => json_encode([
                        'media' => ['gallery' => ['product-galleries/example.jpg']],
                    ], JSON_THROW_ON_ERROR),
                ]),
                $this->productRow(600, 'New Product', 'new-product', 100),
            ],
        );

        $this->artisan('catalog:sync', [
            '--source' => $this->sourceDirectory,
        ])->assertExitCode(0);

        $root->refresh();
        $child = ProductCategory::query()->where('slug', 'catalog-child')->firstOrFail();
        $existingProduct->refresh();
        $newProduct = Product::query()->where('slug', 'new-product')->firstOrFail();

        $this->assertNotSame(100, $root->getKey());
        $this->assertNotSame(200, $child->getKey());
        $this->assertSame('Catalog Root', $root->name);
        $this->assertSame($root->getKey(), $child->parent_id);
        $this->assertSame('Updated Product', $existingProduct->name);
        $this->assertSame(275, $existingProduct->weight);
        $this->assertSame($child->getKey(), $existingProduct->product_category_id);
        $this->assertTrue($existingProduct->is_active);
        $this->assertSame(['First', 'Second'], $existingProduct->bullet_points);
        $this->assertSame(
            ['media' => ['gallery' => ['product-galleries/example.jpg']]],
            $existingProduct->product_config,
        );
        $this->assertSame($root->getKey(), $newProduct->product_category_id);
        $this->assertDatabaseHas('product_categories', ['id' => $remoteOnly->getKey()]);
    }

    public function test_prune_deletes_only_catalog_records_absent_from_the_snapshot(): void
    {
        $root = ProductCategory::create([
            'name' => 'Catalog Root',
            'slug' => 'catalog-root',
        ]);
        $staleCategory = ProductCategory::create([
            'name' => 'Stale Category',
            'slug' => 'stale-category',
        ]);
        $desiredProduct = Product::create([
            'name' => 'Desired Product',
            'slug' => 'desired-product',
            'product_category_id' => $root->getKey(),
            'is_active' => true,
        ]);
        $staleProduct = Product::create([
            'name' => 'Stale Product',
            'slug' => 'stale-product',
            'product_category_id' => $staleCategory->getKey(),
            'is_active' => true,
        ]);

        $this->writeSnapshot(
            categories: [
                $this->categoryRow(100, 'Catalog Root', 'catalog-root'),
            ],
            products: [
                $this->productRow(500, 'Desired Product', 'desired-product', 100),
            ],
        );

        $this->artisan('catalog:sync', [
            '--source' => $this->sourceDirectory,
            '--prune' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('product_categories', ['id' => $root->getKey()]);
        $this->assertDatabaseHas('products', ['id' => $desiredProduct->getKey()]);
        $this->assertDatabaseMissing('product_categories', ['id' => $staleCategory->getKey()]);
        $this->assertDatabaseMissing('products', ['id' => $staleProduct->getKey()]);
    }

    public function test_invalid_snapshot_fails_before_any_database_changes_are_written(): void
    {
        $root = ProductCategory::create([
            'name' => 'Remote Root',
            'slug' => 'catalog-root',
        ]);

        $this->writeSnapshot(
            categories: [
                $this->categoryRow(100, 'Changed Root', 'catalog-root'),
            ],
            products: [
                $this->productRow(500, 'Broken Product', 'broken-product', 100, [
                    'product_config' => '{invalid json',
                ]),
            ],
        );

        $this->artisan('catalog:sync', [
            '--source' => $this->sourceDirectory,
        ])->assertExitCode(1);

        $this->assertSame('Remote Root', $root->refresh()->name);
        $this->assertDatabaseMissing('products', ['slug' => 'broken-product']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<int, array<string, mixed>>  $products
     */
    private function writeSnapshot(array $categories, array $products): void
    {
        File::put(
            $this->sourceDirectory.DIRECTORY_SEPARATOR.'product_categories.json',
            json_encode($categories, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        File::put(
            $this->sourceDirectory.DIRECTORY_SEPARATOR.'products.json',
            json_encode($products, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryRow(
        int $id,
        string $name,
        string $slug,
        ?int $parentId = null,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function productRow(
        int $id,
        string $name,
        string $slug,
        int $categoryId,
        array $attributes = [],
    ): array {
        return array_merge([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'product_category_id' => $categoryId,
            'is_active' => 1,
        ], $attributes);
    }
}
