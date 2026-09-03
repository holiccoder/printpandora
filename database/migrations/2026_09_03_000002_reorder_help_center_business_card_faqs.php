<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SIZES_QUESTION = 'Which business card sizes do you offer?';

    private const WHY_QUESTION = 'Why choose InkPavo business cards?';

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('faqs')
                ->where('question', self::WHY_QUESTION)
                ->update([
                    'sort_order' => 1,
                    'updated_at' => now(),
                ]);

            DB::table('faqs')
                ->where('question', self::SIZES_QUESTION)
                ->update([
                    'sort_order' => 2,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('faqs')
                ->where('question', self::WHY_QUESTION)
                ->update([
                    'sort_order' => 2,
                    'updated_at' => now(),
                ]);

            DB::table('faqs')
                ->where('question', self::SIZES_QUESTION)
                ->update([
                    'sort_order' => 1,
                    'updated_at' => now(),
                ]);
        });
    }
};
