<?php

use App\Models\Product;
use App\Support\FlyersAndBrochuresProductCatalog;
use Database\Seeders\FlyersAndBrochuresProductSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new FlyersAndBrochuresProductSeeder)->run();
    }

    public function down(): void
    {
        Product::query()
            ->whereIn('slug', FlyersAndBrochuresProductCatalog::slugs())
            ->delete();
    }
};
