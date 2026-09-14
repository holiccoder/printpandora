<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $category = DB::table('product_categories')
            ->where('slug', StickerProductCatalog::CATEGORY_SLUG)
            ->first();

        $categoryId = $category?->id;

        if ($categoryId === null) {
            $categoryId = DB::table('product_categories')->insertGetId([
                'name' => 'Stickers & Labels',
                'slug' => StickerProductCatalog::CATEGORY_SLUG,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (StickerProductCatalog::definitions() as $definition) {
            DB::table('products')->updateOrInsert(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'subtitle' => $definition['subtitle'],
                    'description_title' => $definition['description_title'],
                    'description' => $definition['description'],
                    'bullet_points' => $this->encode($definition['bullet_points']),
                    'meta_description' => $definition['meta_description'],
                    'price_line' => $definition['price_line'],
                    'weight' => $definition['weight'],
                    'featured_image' => $definition['featured_image'],
                    'product_category_id' => $categoryId,
                    'product_config' => $this->encode($definition['product_config']),
                    'is_active' => $definition['is_active'],
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->delete();
    }

    private function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
