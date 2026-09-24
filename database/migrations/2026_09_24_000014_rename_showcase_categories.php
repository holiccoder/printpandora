<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const RENAMES = [
        'cotton-paper-business-cards' => 'Cotton Business Cards',
        'folded-brochures' => 'Brochures',
        'finishing-techniques' => 'Finishes',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $slug => $name) {
            DB::table('showcase_categories')
                ->where('slug', $slug)
                ->update([
                    'name' => $name,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $originalNames = [
            'cotton-paper-business-cards' => 'Cotton Paper Business Cards',
            'folded-brochures' => 'Folded Brochures',
            'finishing-techniques' => 'Finishing Techniques',
        ];

        foreach ($originalNames as $slug => $name) {
            DB::table('showcase_categories')
                ->where('slug', $slug)
                ->update([
                    'name' => $name,
                    'updated_at' => now(),
                ]);
        }
    }
};
