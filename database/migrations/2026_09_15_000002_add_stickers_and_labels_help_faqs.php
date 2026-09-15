<?php

use App\Models\Faq;
use App\Models\HelpCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORY_SLUG = 'stickers-and-labels-faq';

    public function up(): void
    {
        $faqs = require database_path('seeders/data/help_stickers_labels_faqs.php');

        DB::transaction(function () use ($faqs): void {
            $category = HelpCategory::updateOrCreate(
                ['slug' => self::CATEGORY_SLUG],
                [
                    'name' => 'stickers and labels faq',
                    'description' => 'frequently asked questions and answers about stickers and labels',
                    'icon' => 'tag',
                    'sort_order' => 6,
                    'is_active' => true,
                ],
            );

            foreach ($faqs as $index => $faq) {
                Faq::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'question' => $faq['question'],
                    ],
                    [
                        'answer' => $faq['answer'],
                        'sort_order' => 100 + $index,
                        'is_published' => true,
                    ],
                );
            }
        });
    }

    public function down(): void
    {
        $faqs = require database_path('seeders/data/help_stickers_labels_faqs.php');

        DB::transaction(function () use ($faqs): void {
            $category = HelpCategory::query()
                ->where('slug', self::CATEGORY_SLUG)
                ->first();

            if ($category === null) {
                return;
            }

            Faq::query()
                ->where('category_id', $category->id)
                ->whereIn('question', array_column($faqs, 'question'))
                ->delete();

            if (! $category->articles()->exists() && ! $category->faqs()->exists()) {
                $category->delete();
            }
        });
    }
};
