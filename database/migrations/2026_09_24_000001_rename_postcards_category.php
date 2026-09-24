<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameCategory('postcards', 'cards-and-postcards', 'Cards & Postcards');
    }

    public function down(): void
    {
        $this->renameCategory('cards-and-postcards', 'postcards', 'Postcards');
    }

    private function renameCategory(string $from, string $to, string $name): void
    {
        $source = DB::table('product_categories')->where('slug', $from)->first();
        $target = DB::table('product_categories')->where('slug', $to)->first();

        if ($source !== null && $target === null) {
            DB::table('product_categories')
                ->where('id', $source->id)
                ->update([
                    'name' => $name,
                    'slug' => $to,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($source !== null && $target !== null && $source->id !== $target->id) {
            DB::table('products')
                ->where('product_category_id', $source->id)
                ->update(['product_category_id' => $target->id]);

            DB::table('product_categories')->where('id', $source->id)->delete();
        }

        if ($target !== null) {
            DB::table('product_categories')
                ->where('id', $target->id)
                ->update([
                    'name' => $name,
                    'slug' => $to,
                    'updated_at' => now(),
                ]);
        }
    }
};
