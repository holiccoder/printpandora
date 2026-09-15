<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (StickerProductCatalog::slugs() as $slug) {
            $product = DB::table('products')
                ->select(['id', 'product_config'])
                ->where('slug', $slug)
                ->first();

            if ($product === null) {
                continue;
            }

            $config = $this->decode($product->product_config ?? null);
            $config['faq'] = StickerProductCatalog::faq();

            DB::table('products')
                ->where('id', $product->id)
                ->update(['product_config' => $this->encode($config)]);
        }
    }

    public function down(): void
    {
        foreach (StickerProductCatalog::slugs() as $slug) {
            $product = DB::table('products')
                ->select(['id', 'product_config'])
                ->where('slug', $slug)
                ->first();

            if ($product === null) {
                continue;
            }

            $config = $this->decode($product->product_config ?? null);

            if (($config['faq'] ?? null) !== StickerProductCatalog::faq()) {
                continue;
            }

            $config['faq'] = [];

            DB::table('products')
                ->where('id', $product->id)
                ->update(['product_config' => $this->encode($config)]);
        }
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
