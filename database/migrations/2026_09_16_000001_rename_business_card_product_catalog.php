<?php

use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{slug: string, name: string, category: string}>
     */
    private const RENAMES = [
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

    /**
     * @var array<string, string>
     */
    private const USER_FACING_REFERENCE_REPLACEMENTS = [
        '/business-cards/super' => '/business-cards/super-standard',
        '/business-cards/luxe' => '/business-cards/super-luxe',
        '/business-cards/classic-quality' => '/business-cards/standard-quality',
        '/business-cards/classic-solid' => '/business-cards/solid-quality',
        '/super-business-cards' => '/business-cards/super-standard',
        '/luxe-business-cards' => '/business-cards/super-luxe',
        '/classic-quality-business-cards' => '/business-cards/standard-quality',
        '/classic-solid-business-cards' => '/business-cards/solid-quality',
        'Super Business Cards' => 'Super Standard Business Cards',
        'Luxe Business Cards' => 'Super Luxe Business Cards',
        'Classic Quality Business Cards' => 'Standard Quality Business Cards',
        'Classic Solid Business Cards' => 'Solid Quality Business Cards',
        'Shop Super Business Cards →' => 'Shop Super Standard Business Cards →',
        'Shop Super Business Cards ›' => 'Shop Super Standard Business Cards ›',
        'Shop Luxe Business Cards →' => 'Shop Super Luxe Business Cards →',
        'Shop Luxe Business Cards ›' => 'Shop Super Luxe Business Cards ›',
        'Shop Classic Quality Business Cards →' => 'Shop Standard Quality Business Cards →',
        'Shop Classic Quality Business Cards ›' => 'Shop Standard Quality Business Cards ›',
        'Shop Classic Solid Business Cards →' => 'Shop Solid Quality Business Cards →',
        'Shop Classic Solid Business Cards ›' => 'Shop Solid Quality Business Cards ›',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $businessCardsId = DB::table('product_categories')
                ->where('slug', 'business-cards')
                ->value('id');
            $categoryIds = $this->synchronizeCategories($businessCardsId);

            foreach (self::RENAMES as $oldSlug => $rename) {
                $oldProduct = DB::table('products')->where('slug', $oldSlug)->first();
                $newProduct = DB::table('products')->where('slug', $rename['slug'])->first();

                if ($oldProduct !== null && $newProduct !== null && $oldProduct->id !== $newProduct->id) {
                    throw new RuntimeException(
                        "Cannot rename {$oldSlug}: {$rename['slug']} already belongs to another product.",
                    );
                }

                $product = $oldProduct ?? $newProduct;

                if ($product === null) {
                    continue;
                }

                $updates = [
                    'name' => $rename['name'],
                    'slug' => $rename['slug'],
                ];

                $productConfig = $this->decodeJsonColumn($product->product_config ?? null);

                if ($productConfig !== null) {
                    $productConfig = $this->renameReferences($productConfig);
                    $productConfig['product'] = is_array($productConfig['product'] ?? null)
                        ? $productConfig['product']
                        : [];
                    $productConfig['product']['name'] = $rename['name'];
                    $productConfig['product']['slug'] = $rename['slug'];
                    $updates['product_config'] = $this->encodeJson($productConfig);
                }

                $productOptions = $this->decodeJsonColumn($product->product_options ?? null);

                if ($productOptions !== null) {
                    $updates['product_options'] = $this->encodeJson(
                        $this->renameReferences($productOptions),
                    );
                }

                if (isset($categoryIds[$rename['category']])) {
                    $updates['product_category_id'] = $categoryIds[$rename['category']];
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Product renames are business data and are intentionally not reversed.
    }

    /**
     * @return array<string, int>
     */
    private function synchronizeCategories(mixed $businessCardsId): array
    {
        if ($businessCardsId === null) {
            return [];
        }

        $categoryIds = [];

        foreach ([
            ['slug' => 'super-business-cards', 'name' => 'Super Business Cards'],
            ['slug' => 'quality-business-cards', 'name' => 'Quality Business Cards'],
        ] as $definition) {
            $category = ProductCategory::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'parent_id' => (int) $businessCardsId,
                ],
            );

            $categoryIds[$definition['slug']] = (int) $category->getKey();
        }

        return $categoryIds;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonColumn(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function renameReferences(array $value): array
    {
        foreach ($value as $key => $child) {
            if (is_array($child)) {
                $value[$key] = $this->renameReferences($child);

                continue;
            }

            if (is_string($child)) {
                $value[$key] = self::USER_FACING_REFERENCE_REPLACEMENTS[$child] ?? $child;
            }
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encodeJson(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
