<?php

use App\Support\PostcardProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', ['super-standard-postcards', 'super-luxe-postcards'])
            ->select(['id', 'slug', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                foreach (['product_config', 'product_options'] as $column) {
                    $payload = json_decode($product->{$column} ?? 'null', true, 512, JSON_THROW_ON_ERROR);

                    if (! is_array($payload)) {
                        continue;
                    }

                    $normalized = PostcardProductCatalog::normalizeOptions($payload, $product->slug);

                    if (is_array($normalized['options'] ?? null)) {
                        $normalized['options'] = PostcardProductCatalog::normalizeOptions(
                            $normalized['options'],
                            $product->slug,
                        );
                    }

                    if ($normalized !== $payload) {
                        $updates[$column] = json_encode(
                            $normalized,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        );
                    }
                }

                if ($updates !== []) {
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        // Removed option groups are intentionally not restored on rollback.
    }
};
