<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductCategory;

final class DesignServiceProduct
{
    public const SLUG = 'design-service';

    public static function resolve(): Product
    {
        $category = ProductCategory::query()->firstOrCreate(
            ['slug' => 'design-services'],
            ['name' => 'Design Services'],
        );

        return Product::query()->firstOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Design Service',
                'description' => '<p>One-time business card design service.</p>',
                'product_category_id' => $category->getKey(),
                'is_active' => false,
            ],
        );
    }
}
