<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categoryId = ProductCategory::where('slug', 'business-cards')->value('id');

        if (! $categoryId) {
            return;
        }

        $variants = [
            [
                'name' => '300g tongbangzhi+uv',
                'slug' => '300g-tongbangzhi-uv',
                'description' => '<p>A classic business card printed on 300 gsm coated stock with a glossy UV finish. Smooth, vibrant, and designed to catch the light — and the eye.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=1200&q=70',
            ],
            [
                'name' => '300g yishuzhi',
                'slug' => '300g-yishuzhi',
                'description' => '<p>Our artistic take on the classic card. Printed on 300 gsm textured art paper for a tactile, premium feel that stands out from the stack.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1583912267550-d6c2ac3196c0?auto=format&fit=crop&w=1200&q=70',
            ],
            [
                'name' => '320g tongbanzhi',
                'slug' => '320g-tongbanzhi',
                'description' => '<p>A step up in substance. 320 gsm coated board gives these business cards a heavier, more premium hand-feel while keeping colors sharp and bright.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1517242810446-cc8951b2be40?auto=format&fit=crop&w=1200&q=70',
            ],
            [
                'name' => '350g baika',
                'slug' => '350g-baika',
                'description' => '<p>Our thickest classic card. 350 gsm white board delivers a crisp, clean canvas with a substantial weight that feels as professional as it looks.</p>',
                'featured_image' => 'https://images.unsplash.com/photo-1542435503-956c469947f6?auto=format&fit=crop&w=1200&q=70',
            ],
        ];

        foreach ($variants as $variant) {
            Product::firstOrCreate(
                ['slug' => $variant['slug']],
                [
                    'name' => $variant['name'],
                    'description' => $variant['description'],
                    'featured_image' => $variant['featured_image'],
                    'product_category_id' => $categoryId,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Product::whereIn('slug', [
            '300g-tongbangzhi-uv',
            '300g-yishuzhi',
            '320g-tongbanzhi',
            '350g-baika',
        ])->delete();
    }
};
