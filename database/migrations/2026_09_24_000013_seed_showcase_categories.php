<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, array{name: string, slug: string, sort_order: int}>
     */
    private const CATEGORIES = [
        [
            'name' => 'Cotton Paper Business Cards',
            'slug' => 'cotton-paper-business-cards',
            'sort_order' => 10,
        ],
        [
            'name' => 'Premium Business Cards',
            'slug' => 'premium-business-cards',
            'sort_order' => 20,
        ],
        [
            'name' => 'Metal Business Cards',
            'slug' => 'metal-business-cards',
            'sort_order' => 30,
        ],
        [
            'name' => 'Stickers & Labels',
            'slug' => 'stickers-labels',
            'sort_order' => 40,
        ],
        [
            'name' => 'Folded Brochures',
            'slug' => 'folded-brochures',
            'sort_order' => 50,
        ],
        [
            'name' => 'Cards & Postcards',
            'slug' => 'cards-and-postcards',
            'sort_order' => 60,
        ],
        [
            'name' => 'Paper Stocks',
            'slug' => 'paper-stocks',
            'sort_order' => 70,
        ],
        [
            'name' => 'Finishing Techniques',
            'slug' => 'finishing-techniques',
            'sort_order' => 80,
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::CATEGORIES as $category) {
            DB::table('showcase_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('showcase_categories')
            ->whereIn('slug', array_column(self::CATEGORIES, 'slug'))
            ->delete();
    }
};
