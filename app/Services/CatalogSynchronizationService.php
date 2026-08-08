<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

/**
 * @phpstan-type CategorySnapshot array{name: string, slug: string, parent_slug: string|null}
 * @phpstan-type ProductSnapshot array{slug: string, category_slug: string, attributes: array<string, mixed>}
 * @phpstan-type CatalogSnapshot array{categories: array<string, CategorySnapshot>, products: array<string, ProductSnapshot>}
 * @phpstan-type SyncCounts array{create: int, update: int, unchanged: int, delete: int}
 * @phpstan-type SyncSummary array{categories: SyncCounts, products: SyncCounts}
 */
class CatalogSynchronizationService
{
    /** @var array<int, string> */
    private const PRODUCT_STRING_FIELDS = [
        'subtitle',
        'description_title',
        'description',
        'price_line',
        'meta_description',
        'featured_image',
    ];

    /** @var array<int, string> */
    private const PRODUCT_JSON_FIELDS = [
        'bullet_points',
        'product_options',
        'product_config',
    ];

    /**
     * @return SyncSummary
     */
    public function synchronize(
        string $sourceDirectory,
        bool $dryRun = false,
        bool $prune = false,
    ): array {
        $snapshot = $this->loadSnapshot($sourceDirectory);

        if ($dryRun) {
            return $this->buildSummary($snapshot, $prune);
        }

        return DB::transaction(function () use ($snapshot, $prune): array {
            $summary = $this->buildSummary($snapshot, $prune, lockForUpdate: true);

            $this->applySnapshot($snapshot, $prune);

            return $summary;
        }, attempts: 3);
    }

    /**
     * @return CatalogSnapshot
     */
    private function loadSnapshot(string $sourceDirectory): array
    {
        if (! is_dir($sourceDirectory)) {
            throw new RuntimeException("Catalog source directory does not exist: {$sourceDirectory}");
        }

        $sourceDirectory = rtrim($sourceDirectory, '/\\');
        $categoryRows = $this->readRows(
            $sourceDirectory.DIRECTORY_SEPARATOR.'product_categories.json',
            'product categories',
        );
        $productRows = $this->readRows(
            $sourceDirectory.DIRECTORY_SEPARATOR.'products.json',
            'products',
        );

        [$categories, $categoryIdToSlug] = $this->normalizeCategories($categoryRows);
        $products = $this->normalizeProducts($productRows, $categories, $categoryIdToSlug);

        return [
            'categories' => $categories,
            'products' => $products,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $path, string $label): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("The {$label} snapshot is missing or unreadable: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read the {$label} snapshot: {$path}");
        }

        try {
            $rows = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "The {$label} snapshot contains invalid JSON: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new RuntimeException("The {$label} snapshot must contain a JSON array.");
        }

        if ($rows === []) {
            throw new RuntimeException("The {$label} snapshot is empty; synchronization was stopped for safety.");
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                throw new RuntimeException("The {$label} snapshot row {$index} must be a JSON object.");
            }
        }

        /** @var array<int, array<string, mixed>> $rows */
        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: array<string, CategorySnapshot>, 1: array<string, string>}
     */
    private function normalizeCategories(array $rows): array
    {
        /** @var array<string, array<string, mixed>> $rowsBySlug */
        $rowsBySlug = [];
        /** @var array<string, string> $idToSlug */
        $idToSlug = [];

        foreach ($rows as $index => $row) {
            $context = "product category row {$index}";
            $slug = $this->requiredString($row, 'slug', $context, trim: true);
            $name = $this->requiredString($row, 'name', $context, trim: true);

            $this->assertUniqueSlug($rowsBySlug, $slug, 'product category');

            if (array_key_exists('id', $row) && $row['id'] !== null) {
                $sourceId = $this->sourceIdentifier($row['id'], "{$context}.id");

                if (isset($idToSlug[$sourceId])) {
                    throw new RuntimeException("Duplicate source product category ID: {$sourceId}");
                }

                $idToSlug[$sourceId] = $slug;
            }

            $rowsBySlug[$slug] = $row + [
                'name' => $name,
                'slug' => $slug,
            ];
        }

        /** @var array<string, CategorySnapshot> $categories */
        $categories = [];

        foreach ($rowsBySlug as $slug => $row) {
            $parentSlug = null;

            if (array_key_exists('parent_slug', $row)) {
                $parentSlug = $this->nullableString($row['parent_slug'], "product category {$slug}.parent_slug", trim: true);
            } elseif (($row['parent_id'] ?? null) !== null) {
                $parentId = $this->sourceIdentifier($row['parent_id'], "product category {$slug}.parent_id");
                $parentSlug = $idToSlug[$parentId] ?? throw new RuntimeException(
                    "Product category {$slug} references missing source parent ID {$parentId}.",
                );
            }

            if ($parentSlug !== null && ! isset($rowsBySlug[$parentSlug])) {
                throw new RuntimeException(
                    "Product category {$slug} references missing parent slug {$parentSlug}.",
                );
            }

            if ($parentSlug === $slug) {
                throw new RuntimeException("Product category {$slug} cannot be its own parent.");
            }

            $categories[$slug] = [
                'name' => (string) $row['name'],
                'slug' => $slug,
                'parent_slug' => $parentSlug,
            ];
        }

        $this->assertCategoryHierarchyIsAcyclic($categories);

        return [$categories, $idToSlug];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, CategorySnapshot>  $categories
     * @param  array<string, string>  $categoryIdToSlug
     * @return array<string, ProductSnapshot>
     */
    private function normalizeProducts(
        array $rows,
        array $categories,
        array $categoryIdToSlug,
    ): array {
        /** @var array<string, ProductSnapshot> $products */
        $products = [];

        foreach ($rows as $index => $row) {
            $context = "product row {$index}";
            $slug = $this->requiredString($row, 'slug', $context, trim: true);
            $name = $this->requiredString($row, 'name', $context, trim: true);

            $this->assertUniqueSlug($products, $slug, 'product');

            if (array_key_exists('category_slug', $row) && $row['category_slug'] !== null) {
                $categorySlug = $this->requiredString($row, 'category_slug', $context, trim: true);
            } else {
                $categoryId = $this->sourceIdentifier(
                    $row['product_category_id'] ?? null,
                    "{$context}.product_category_id",
                );
                $categorySlug = $categoryIdToSlug[$categoryId] ?? throw new RuntimeException(
                    "Product {$slug} references missing source category ID {$categoryId}.",
                );
            }

            if (! isset($categories[$categorySlug])) {
                throw new RuntimeException(
                    "Product {$slug} references category slug {$categorySlug}, which is not in the snapshot.",
                );
            }

            $attributes = ['name' => $name];

            foreach (self::PRODUCT_STRING_FIELDS as $field) {
                if (array_key_exists($field, $row)) {
                    $attributes[$field] = $this->nullableString($row[$field], "product {$slug}.{$field}");
                }
            }

            foreach (self::PRODUCT_JSON_FIELDS as $field) {
                if (array_key_exists($field, $row)) {
                    $attributes[$field] = $this->nullableJsonArray($row[$field], "product {$slug}.{$field}");
                }
            }

            if (array_key_exists('is_active', $row)) {
                $attributes['is_active'] = $this->booleanValue($row['is_active'], "product {$slug}.is_active");
            }

            $products[$slug] = [
                'slug' => $slug,
                'category_slug' => $categorySlug,
                'attributes' => $attributes,
            ];
        }

        return $products;
    }

    /**
     * @param  CatalogSnapshot  $snapshot
     * @return SyncSummary
     */
    private function buildSummary(
        array $snapshot,
        bool $prune,
        bool $lockForUpdate = false,
    ): array {
        $categoryQuery = ProductCategory::query()
            ->with('parent')
            ->orderBy('id');
        $productQuery = Product::query()
            ->with('category')
            ->orderBy('id');

        if ($lockForUpdate) {
            $categoryQuery->lockForUpdate();
            $productQuery->lockForUpdate();
        }

        $currentCategories = $categoryQuery
            ->get()
            ->keyBy(fn (ProductCategory $category): string => $category->slug);
        $currentProducts = $productQuery
            ->get()
            ->keyBy(fn (Product $product): string => (string) $product->slug);

        $categoryCounts = $this->emptyCounts();

        foreach ($snapshot['categories'] as $slug => $desired) {
            $existing = $currentCategories->get($slug);

            if (! $existing instanceof ProductCategory) {
                $categoryCounts['create']++;

                continue;
            }

            $isChanged = $existing->name !== $desired['name']
                || $existing->parent?->slug !== $desired['parent_slug'];

            $categoryCounts[$isChanged ? 'update' : 'unchanged']++;
        }

        if ($prune) {
            $categoryCounts['delete'] = $currentCategories
                ->keys()
                ->diff(array_keys($snapshot['categories']))
                ->count();
        }

        $productCounts = $this->emptyCounts();

        foreach ($snapshot['products'] as $slug => $desired) {
            $existing = $currentProducts->get($slug);

            if (! $existing instanceof Product) {
                $productCounts['create']++;

                continue;
            }

            $isChanged = $existing->category?->slug !== $desired['category_slug'];

            foreach ($desired['attributes'] as $attribute => $value) {
                if (! $this->valuesAreEquivalent($existing->getAttribute($attribute), $value)) {
                    $isChanged = true;
                    break;
                }
            }

            $productCounts[$isChanged ? 'update' : 'unchanged']++;
        }

        if ($prune) {
            $productCounts['delete'] = $currentProducts
                ->keys()
                ->diff(array_keys($snapshot['products']))
                ->count();
        }

        return [
            'categories' => $categoryCounts,
            'products' => $productCounts,
        ];
    }

    /**
     * @param  CatalogSnapshot  $snapshot
     */
    private function applySnapshot(array $snapshot, bool $prune): void
    {
        $categorySlugs = array_keys($snapshot['categories']);
        $productSlugs = array_keys($snapshot['products']);

        if ($prune) {
            $this->deleteProductsNotIn($productSlugs);
        }

        // Clear imported relationships first so valid hierarchy moves cannot be
        // rejected because of the previous hierarchy's transient state.
        ProductCategory::query()
            ->whereIn('slug', $categorySlugs)
            ->update(['parent_id' => null]);

        foreach ($snapshot['categories'] as $slug => $desired) {
            $category = ProductCategory::query()->firstOrNew(['slug' => $slug]);
            $category->name = $desired['name'];
            $category->parent_id = null;

            if (! $category->exists || $category->isDirty()) {
                $category->save();
            }
        }

        $categoriesBySlug = ProductCategory::query()
            ->whereIn('slug', $categorySlugs)
            ->get()
            ->keyBy(fn (ProductCategory $category): string => $category->slug);

        foreach ($snapshot['categories'] as $slug => $desired) {
            $category = $categoriesBySlug->get($slug);

            if (! $category instanceof ProductCategory) {
                throw new RuntimeException("Failed to resolve synchronized product category {$slug}.");
            }

            $parent = $desired['parent_slug'] === null
                ? null
                : $categoriesBySlug->get($desired['parent_slug']);

            if ($desired['parent_slug'] !== null && ! $parent instanceof ProductCategory) {
                throw new RuntimeException("Failed to resolve parent category {$desired['parent_slug']}.");
            }

            $category->parent_id = $parent?->getKey();

            if ($category->isDirty('parent_id')) {
                $category->save();
            }
        }

        foreach ($snapshot['products'] as $slug => $desired) {
            $category = $categoriesBySlug->get($desired['category_slug']);

            if (! $category instanceof ProductCategory) {
                throw new RuntimeException("Failed to resolve category {$desired['category_slug']} for product {$slug}.");
            }

            $product = Product::query()->firstOrNew(['slug' => $slug]);
            $product->fill($desired['attributes']);
            $product->product_category_id = $category->getKey();

            if (! $product->exists || $product->isDirty()) {
                $product->save();
            }
        }

        if ($prune) {
            $this->deleteCategoriesNotIn($categorySlugs);
        }
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function deleteProductsNotIn(array $slugs): void
    {
        $this->recordsNotIn(Product::query(), $slugs)
            ->orderBy('id')
            ->each(function (Product $product): void {
                $product->delete();
            });
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function deleteCategoriesNotIn(array $slugs): void
    {
        $this->recordsNotIn(ProductCategory::query(), $slugs)
            ->orderByDesc('id')
            ->each(function (ProductCategory $category): void {
                $category->delete();
            });
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $slugs
     * @return Builder<TModel>
     */
    private function recordsNotIn(Builder $query, array $slugs): Builder
    {
        return $query->whereNotIn('slug', $slugs);
    }

    /**
     * @param  array<string, CategorySnapshot>  $categories
     */
    private function assertCategoryHierarchyIsAcyclic(array $categories): void
    {
        foreach (array_keys($categories) as $startSlug) {
            $visited = [];
            $slug = $startSlug;

            while ($slug !== null) {
                if (isset($visited[$slug])) {
                    throw new RuntimeException("Product category hierarchy contains a cycle involving {$slug}.");
                }

                $visited[$slug] = true;
                $slug = $categories[$slug]['parent_slug'];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function requiredString(
        array $row,
        string $field,
        string $context,
        bool $trim = false,
    ): string {
        if (! array_key_exists($field, $row) || ! is_string($row[$field])) {
            throw new RuntimeException("{$context}.{$field} must be a string.");
        }

        $value = $trim ? trim($row[$field]) : $row[$field];

        if ($value === '') {
            throw new RuntimeException("{$context}.{$field} cannot be empty.");
        }

        return $value;
    }

    private function nullableString(mixed $value, string $context, bool $trim = false): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new RuntimeException("{$context} must be a string or null.");
        }

        $value = $trim ? trim($value) : $value;

        return $value === '' && $trim ? null : $value;
    }

    /**
     * @return array<mixed>|null
     */
    private function nullableJsonArray(mixed $value, string $context): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            throw new RuntimeException("{$context} must be a JSON object, JSON array, or null.");
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "{$context} contains invalid JSON: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("{$context} must decode to a JSON object or array.");
        }

        return $decoded;
    }

    private function booleanValue(mixed $value, string $context): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        throw new RuntimeException("{$context} must be a boolean value.");
    }

    private function sourceIdentifier(mixed $value, string $context): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        throw new RuntimeException("{$context} must contain a source identifier.");
    }

    /**
     * @param  array<string, mixed>  $records
     */
    private function assertUniqueSlug(array $records, string $slug, string $recordType): void
    {
        if (isset($records[$slug])) {
            throw new RuntimeException("Duplicate {$recordType} slug in snapshot: {$slug}");
        }
    }

    private function valuesAreEquivalent(mixed $current, mixed $desired): bool
    {
        return $this->normalizeComparableValue($current) === $this->normalizeComparableValue($desired);
    }

    private function normalizeComparableValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normalizeComparableValue($item),
                $value,
            );
        }

        ksort($value);

        return array_map(
            fn (mixed $item): mixed => $this->normalizeComparableValue($item),
            $value,
        );
    }

    /**
     * @return SyncCounts
     */
    private function emptyCounts(): array
    {
        return [
            'create' => 0,
            'update' => 0,
            'unchanged' => 0,
            'delete' => 0,
        ];
    }
}
