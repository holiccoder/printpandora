<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\StickerProductCatalog;
use Illuminate\Database\Seeder;

class StickerProductOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => StickerProductCatalog::CATEGORY_SLUG],
            ['name' => 'Stickers & Labels', 'parent_id' => null],
        );

        foreach (StickerProductCatalog::definitions() as $definition) {
            Product::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'subtitle' => $definition['subtitle'],
                    'description_title' => $definition['description_title'],
                    'description' => $definition['description'],
                    'bullet_points' => $definition['bullet_points'],
                    'meta_description' => $definition['meta_description'],
                    'price_line' => $definition['price_line'],
                    'weight' => $definition['weight'],
                    'featured_image' => $definition['featured_image'],
                    'product_category_id' => $category->id,
                    'product_config' => $definition['product_config'],
                    'is_active' => $definition['is_active'],
                ],
            );
        }
    }
}
