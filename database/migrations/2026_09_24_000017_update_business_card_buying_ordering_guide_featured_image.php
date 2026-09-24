<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SLUG = 'business-card-buying-ordering-guide';

    private const OLD_IMAGE = '/images/blog/business-card-buying-ordering-guide-featured.webp';

    private const NEW_IMAGE = '/images/blog/business-card-buying-ordering-guide-featured.png';

    public function up(): void
    {
        DB::table('posts')
            ->where('slug', self::SLUG)
            ->update([
                'featured_image' => self::NEW_IMAGE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('posts')
            ->where('slug', self::SLUG)
            ->where('featured_image', self::NEW_IMAGE)
            ->update([
                'featured_image' => self::OLD_IMAGE,
                'updated_at' => now(),
            ]);
    }
};
