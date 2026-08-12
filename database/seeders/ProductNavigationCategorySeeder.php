<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductNavigationCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $definitions = [
                ['name' => 'Business Cards', 'slug' => 'business-cards', 'parent_slug' => null],
                ['name' => 'Postcards', 'slug' => 'postcards', 'parent_slug' => null],
                ['name' => 'Stickers & Labels', 'slug' => 'stickers-and-labels', 'parent_slug' => null],
                ['name' => 'Flyers & Brochures', 'slug' => 'flyers-brochures', 'parent_slug' => null],
                ['name' => 'Cotton Business Cards', 'slug' => 'cotton-business-cards', 'parent_slug' => 'business-cards'],
                ['name' => 'PVC Business Cards', 'slug' => 'pvc-business-cards', 'parent_slug' => 'business-cards'],
                ['name' => 'Metal Business Cards', 'slug' => 'metal-business-cards', 'parent_slug' => 'business-cards'],
                ['name' => 'Classic Business Cards', 'slug' => 'classic-business-cards', 'parent_slug' => 'business-cards'],
            ];

            $categoryIds = [];

            foreach ($definitions as $definition) {
                $category = ProductCategory::updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'parent_id' => $definition['parent_slug']
                            ? ($categoryIds[$definition['parent_slug']] ?? null)
                            : null,
                    ],
                );

                $categoryIds[$definition['slug']] = (int) $category->getKey();
            }

            $businessCardsId = $categoryIds['business-cards'];

            // Products from the old categories that are not present in the
            // header navigation remain available under Business Cards.
            $legacyCategoryIds = ProductCategory::query()
                ->whereIn('slug', ['apparel', 'signage-banners', 'stationery'])
                ->pluck('id');

            if ($legacyCategoryIds->isNotEmpty()) {
                Product::query()
                    ->whereIn('product_category_id', $legacyCategoryIds)
                    ->update(['product_category_id' => $businessCardsId]);
            }

            Product::query()
                ->whereIn('slug', [
                    'classic-business-cards',
                    'classic-standard-business-cards',
                    'classic-special-business-cards',
                    'classic-quality-business-cards',
                    'classic-solid-business-cards',
                ])
                ->update(['product_category_id' => $categoryIds['classic-business-cards']]);

            // Remove categories that are no longer part of the header
            // navigation only after every product has been reassigned.
            ProductCategory::query()
                ->whereNotIn('slug', array_keys($categoryIds))
                ->delete();
        });

        if ($this->command !== null) {
            $this->command->info('Product categories synchronized with the storefront navigation.');
        }
    }
}
