<?php

use App\Models\Faq;
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

        $legacyFaqQuestions = [
            'What file formats do you accept?',
            'How long does production take?',
            'What is your minimum order quantity?',
            'Do you offer design services?',
            'Can I track my order?',
            'What is your refund policy?',
        ];

        DB::table('faqs')
            ->where('category_id', $categoryId)
            ->whereIn('question', $legacyFaqQuestions)
            ->delete();

        $faqs = require database_path('seeders/data/help_popular_faqs.php');

        foreach ($faqs as $sortOrder => $faq) {
            Faq::query()->updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'question' => $faq['question'],
                ],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $sortOrder,
                    'is_published' => true,
                ],
            );
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

        $faqQuestions = require database_path('seeders/data/help_popular_faqs.php');

        DB::table('faqs')
            ->where('category_id', $categoryId)
            ->whereIn('question', array_column($faqQuestions, 'question'))
            ->delete();
    }
};
