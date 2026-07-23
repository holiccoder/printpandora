<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $businessCards = ProductCategory::where('slug', 'business-cards')->value('id');

        if (! $businessCards) {
            return;
        }

        $products = [
            [
                'name' => 'Super Business Cards',
                'slug' => 'super-business-cards',
                'description' => '<p>350 gsm premium paper — a refined upgrade with exceptional detail and a vibrant finish. Thicker, brighter, and built to leave a lasting impression.</p>',
            ],
            [
                'name' => 'Luxe Business Cards',
                'slug' => 'luxe-business-cards',
                'description' => '<p>700 gsm premium paper — our thickest, most luxurious card. An ultra-premium feel that demands attention and conveys uncompromising quality.</p>',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => 0.00,
                    'product_category_id' => $businessCards,
                    'is_active' => true,
                ],
            );
        }
    }

    public function down(): void
    {
        Product::whereIn('slug', [
            'super-business-cards',
            'luxe-business-cards',
        ])->delete();
    }
};
