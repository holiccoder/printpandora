<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Database\Seeder;

class FlyersAndBrochuresProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = ProductCategory::query()->updateOrCreate(
            ['slug' => FlyersAndBrochuresProductCatalog::CATEGORY_SLUG],
            ['name' => 'Flyers & Brochures', 'parent_id' => null],
        );

        foreach (FlyersAndBrochuresProductCatalog::definitions() as $definition) {
            Product::query()->updateOrCreate(
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
                    'product_category_id' => $category->getKey(),
                    'product_config' => $definition['product_config'],
                    'is_active' => $definition['is_active'],
                ],
            );
        }
    }
}
