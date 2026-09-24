<?php

use App\Models\Product;
use App\Support\PostcardProductImageCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (array_keys(PostcardProductImageCatalog::images()) as $productSlug) {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product !== null) {
                PostcardProductImageCatalog::apply($product);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(PostcardProductImageCatalog::images()) as $productSlug) {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product !== null) {
                PostcardProductImageCatalog::restorePrevious($product);
            }
        }
    }
};
