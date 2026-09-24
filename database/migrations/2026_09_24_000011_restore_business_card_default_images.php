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
                PostcardProductImageCatalog::restorePrevious($product);
            }
        }
    }

    /**
     * Keep the restored business-card images in place if this corrective
     * migration is rolled back. The previous migration's postcard images
     * must not be reapplied automatically.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
