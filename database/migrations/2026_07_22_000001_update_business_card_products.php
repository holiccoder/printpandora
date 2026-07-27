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
        $businessCards = ProductCategory::where('slug', 'business-cards')->value('id');

        if (! $businessCards) {
            return;
        }

        // Rename existing classic variants.
        $renames = [
            '300g-tongbangzhi-uv' => ['name' => 'Standard Classic Business card', 'slug' => 'standard-classic-business-card'],
            '300g-yishuzhi' => ['name' => 'Classic special business cards', 'slug' => 'classic-special-business-cards'],
            '320g-tongbanzhi' => ['name' => 'Classic quality business cards', 'slug' => 'classic-quality-business-cards'],
            '350g-baika' => ['name' => 'Classic lush business cards', 'slug' => 'classic-lush-business-cards'],
        ];

        foreach ($renames as $oldSlug => $new) {
            Product::where('slug', $oldSlug)->update([
                'name' => $new['name'],
                'slug' => $new['slug'],
            ]);
        }

        // New categories.
        $cottonCategory = ProductCategory::firstOrCreate(
            ['slug' => 'cotton-business-cards'],
            ['name' => 'Cotton Business Cards'],
        );

        $pvcCategory = ProductCategory::firstOrCreate(
            ['slug' => 'pvc-business-cards'],
            ['name' => 'PVC Business Cards'],
        );

        // Cotton products.
        $cottonProducts = [
            ['name' => 'Basic cotton business card', 'slug' => 'basic-cotton-business-card'],
            ['name' => 'Classic cotton business card', 'slug' => 'classic-cotton-business-card'],
            ['name' => 'Premium cotton business card', 'slug' => 'premium-cotton-business-card'],
            ['name' => 'Luxe cotton business card', 'slug' => 'luxe-cotton-business-card'],
            ['name' => 'Grand cotton business card', 'slug' => 'grand-cotton-business-card'],
        ];

        foreach ($cottonProducts as $product) {
            Product::firstOrCreate(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'description' => '<p>'.e($product['name']).' printed on premium cotton stock.</p>',
                    'price' => 0.00,
                    'product_category_id' => $cottonCategory->id,
                    'is_active' => true,
                ],
            );
        }

        // PVC products.
        $pvcProducts = [
            ['name' => 'Standard PVC card', 'slug' => 'standard-pvc-card'],
            ['name' => 'Premium PVC card', 'slug' => 'premium-pvc-card'],
        ];

        foreach ($pvcProducts as $product) {
            Product::firstOrCreate(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'description' => '<p>'.e($product['name']).' — durable, waterproof and built to last.</p>',
                    'price' => 0.00,
                    'product_category_id' => $pvcCategory->id,
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
        $reverseRenames = [
            'standard-classic-business-card' => ['name' => '300g tongbangzhi+uv', 'slug' => '300g-tongbangzhi-uv'],
            'classic-special-business-cards' => ['name' => '300g yishuzhi', 'slug' => '300g-yishuzhi'],
            'classic-quality-business-cards' => ['name' => '320g tongbanzhi', 'slug' => '320g-tongbanzhi'],
            'classic-lush-business-cards' => ['name' => '350g baika', 'slug' => '350g-baika'],
        ];

        foreach ($reverseRenames as $currentSlug => $old) {
            Product::where('slug', $currentSlug)->update([
                'name' => $old['name'],
                'slug' => $old['slug'],
            ]);
        }

        Product::whereIn('slug', [
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card',
            'standard-pvc-card',
            'premium-pvc-card',
        ])->delete();

        ProductCategory::whereIn('slug', ['cotton-business-cards', 'pvc-business-cards'])->delete();
    }
};
