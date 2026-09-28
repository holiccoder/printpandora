<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductCategory;

final class FreeSamplePackProduct
{
    public const SLUG = 'free-sample-pack';

    public const FIXED_SHIPPING_WEIGHT_GRAMS = 500;

    public static function resolve(): Product
    {
        $category = ProductCategory::query()->firstOrCreate(
            ['slug' => 'business-cards'],
            ['name' => 'Business Cards'],
        );

        return Product::query()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Free Business Card Sample Pack',
                'subtitle' => 'Compare our papers and finishes before you order.',
                'description' => '<p>A free pre-printed business card sample pack. Only delivery is charged.</p>',
                'price_line' => 'Free',
                'featured_image' => '/images/home/sample-pack-banner.png',
                'product_category_id' => $category->getKey(),
                'weight' => 0,
                'shipping_weight_grams' => self::FIXED_SHIPPING_WEIGHT_GRAMS,
                'is_active' => false,
            ],
        );
    }
}
