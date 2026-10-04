<?php

use App\Models\Product;
use App\Support\CardsAndPostcardsProductImageCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (CardsAndPostcardsProductImageCatalog::businessCardSlugs() as $productSlug) {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product !== null) {
                CardsAndPostcardsProductImageCatalog::applyBusinessCard($product);
            }
        }

        foreach (CardsAndPostcardsProductImageCatalog::postcardSlugs() as $productSlug) {
            $product = Product::query()->where('slug', $productSlug)->first();

            if ($product !== null) {
                CardsAndPostcardsProductImageCatalog::applyPostcard($product);
            }
        }
    }

    /**
     * The migration changes the database image pointers, while the matching
     * public image files are intentionally replaced in the repository. A
     * rollback cannot safely reconstruct the previous binary assets.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
