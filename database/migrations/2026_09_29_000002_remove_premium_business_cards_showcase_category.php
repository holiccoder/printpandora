<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORY_SLUG = 'premium-business-cards';

    public function up(): void
    {
        $categoryId = DB::table('showcase_categories')
            ->where('slug', self::CATEGORY_SLUG)
            ->value('id');

        if ($categoryId === null) {
            return;
        }

        DB::table('showcases')
            ->where('category_id', $categoryId)
            ->update([
                'category_id' => null,
                'updated_at' => now(),
            ]);

        DB::table('showcase_categories')
            ->where('id', $categoryId)
            ->delete();
    }

    public function down(): void
    {
        $now = now();

        DB::table('showcase_categories')->updateOrInsert(
            ['slug' => self::CATEGORY_SLUG],
            [
                'name' => 'Premium Business Cards',
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }
};
