<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Support\PostcardProductImageCatalog;
use Illuminate\Database\Seeder;

class PostcardProductImageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(PostcardProductImageCatalog::images()) as $productSlug) {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product !== null) {
                PostcardProductImageCatalog::apply($product);
            }
        }
    }
}
