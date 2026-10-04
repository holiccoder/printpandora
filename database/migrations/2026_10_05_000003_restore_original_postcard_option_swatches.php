<?php

use App\Support\PostcardProductSwatchCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (PostcardProductSwatchCatalog::swatchesForProductSlugs() as $productSlug) {
            $product = DB::table('products')
                ->select(['id', 'product_config', 'product_options'])
                ->where('slug', $productSlug)
                ->first();

            if ($product === null) {
                continue;
            }

            $updates = [];
            $config = $this->decode($product->product_config ?? null);
            $restoredConfig = PostcardProductSwatchCatalog::synchronizeConfig($config, $productSlug);

            if ($restoredConfig !== $config) {
                $updates['product_config'] = $this->encode($restoredConfig);
            }

            $legacy = $this->decode($product->product_options ?? null);
            $restoredLegacy = PostcardProductSwatchCatalog::synchronizeOptions($legacy, $productSlug);

            if ($restoredLegacy !== $legacy) {
                $updates['product_options'] = $this->encode($restoredLegacy);
            }

            if ($updates !== []) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // The restored original swatch assignments are intentionally not reverted.
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $encoded): array
    {
        if (is_array($encoded)) {
            return $encoded;
        }

        if (! is_string($encoded) || trim($encoded) === '') {
            return [];
        }

        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
