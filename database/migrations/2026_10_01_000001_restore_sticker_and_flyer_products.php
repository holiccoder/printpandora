<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use App\Support\StickerProductCatalog;
use Database\Seeders\FlyersAndBrochuresProductSeeder;
use Database\Seeders\StickerProductOptionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new StickerProductOptionsSeeder)->run();
        (new FlyersAndBrochuresProductSeeder)->run();
    }

    public function down(): void
    {
        Product::query()
            ->whereIn('slug', [
                ...StickerProductCatalog::slugs(),
                ...FlyersAndBrochuresProductCatalog::slugs(),
            ])
            ->delete();
    }
};
