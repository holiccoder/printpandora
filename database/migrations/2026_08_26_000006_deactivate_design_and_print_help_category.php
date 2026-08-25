<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('help_categories')
            ->where('slug', 'design-and-print-knowledge')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('help_categories')
            ->where('slug', 'design-and-print-knowledge')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
