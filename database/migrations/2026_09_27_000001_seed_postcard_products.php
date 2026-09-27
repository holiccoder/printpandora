<?php

use App\Models\Product;
use App\Support\PostcardProductCatalog;
use Database\Seeders\PostcardProductSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new PostcardProductSeeder)->run();
    }

    public function down(): void
    {
        Product::query()
            ->whereIn('slug', PostcardProductCatalog::slugs())
            ->delete();
    }
};
