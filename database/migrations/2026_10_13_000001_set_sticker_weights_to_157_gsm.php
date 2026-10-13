<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', ['classic-stickers', 'premium-stickers', 'super-stickers'])
            ->update(['weight' => 157]);
    }

    public function down(): void
    {
        // Previous product weights may have been customized and cannot be inferred.
    }
};
