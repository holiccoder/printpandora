<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonicalQuestion = 'What is the after-sales policy for printing quality issues?';
        $qualityFaq = collect(require database_path('seeders/data/help_popular_faqs.php'))
            ->firstWhere('question', $canonicalQuestion);

        if (! is_array($qualityFaq)) {
            return;
        }

        $legacyQuestion = json_decode(
            '"\u5982\u679c\u5370\u5237\u6709\u8d28\u91cf\u95ee\u9898\u5982\u4f55\u552e\u540e"',
            true,
        );
        $interimQuestion = 'What should I do if my printed order has a quality issue?';
        $sourceQuestions = [$legacyQuestion, $interimQuestion, $canonicalQuestion];

        DB::transaction(function () use ($canonicalQuestion, $qualityFaq, $sourceQuestions): void {
            $rows = DB::table('faqs')
                ->whereIn('question', $sourceQuestions)
                ->orderBy('id')
                ->get();

            $canonicalRow = $rows->firstWhere('question', $canonicalQuestion);
            $rowToKeep = $canonicalRow ?? $rows->first();

            if ($rowToKeep === null) {
                return;
            }

            DB::table('faqs')
                ->where('id', $rowToKeep->id)
                ->update([
                    'question' => $canonicalQuestion,
                    'answer' => $qualityFaq['answer'],
                    'updated_at' => now(),
                ]);

            DB::table('faqs')
                ->whereIn('question', $sourceQuestions)
                ->where('id', '<>', $rowToKeep->id)
                ->delete();
        });
    }

    public function down(): void
    {
        // The English FAQ copy should not be reverted to untranslated content.
    }
};
