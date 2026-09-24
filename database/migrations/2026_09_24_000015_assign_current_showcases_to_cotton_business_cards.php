<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORY_SLUG = 'cotton-paper-business-cards';

    public function up(): void
    {
        $categoryId = DB::table('showcase_categories')
            ->where('slug', self::CATEGORY_SLUG)
            ->value('id');

        if ($categoryId === null) {
            return;
        }

        DB::table('showcases')
            ->whereNull('category_id')
            ->update([
                'category_id' => $categoryId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
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
    }
};
