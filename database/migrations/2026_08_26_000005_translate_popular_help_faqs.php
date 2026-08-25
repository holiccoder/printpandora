<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('help_categories')
            ->where('slug', 'design-and-print-knowledge')
            ->value('id');

        if ($categoryId === null) {
            return;
        }

        $faqs = require database_path('seeders/data/help_popular_faqs.php');
        $sortOrders = array_keys($faqs);

        DB::table('faqs')
            ->where('category_id', $categoryId)
            ->whereIn('sort_order', $sortOrders)
            ->delete();

        $now = now();

        foreach ($faqs as $sortOrder => $faq) {
            DB::table('faqs')->insert([
                'category_id' => $categoryId,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $sortOrder,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $categoryId = DB::table('help_categories')
            ->where('slug', 'design-and-print-knowledge')
            ->value('id');

        if ($categoryId === null) {
            return;
        }

        $faqs = require database_path('seeders/data/help_popular_faqs.php');

        DB::table('faqs')
            ->where('category_id', $categoryId)
            ->whereIn('question', array_column($faqs, 'question'))
            ->delete();
    }
};
