<?php

use App\Support\SolidQualityBusinessCardGallery;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = SolidQualityBusinessCardGallery::PRODUCT_SLUG;

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $updates = [];
        $config = $this->decode($product->product_config ?? null);

        if ($config !== []) {
            $encodedConfig = $this->encode(
                SolidQualityBusinessCardGallery::synchronizeConfig($config),
            );

            if ($encodedConfig !== ($product->product_config ?? null)) {
                $updates['product_config'] = $encodedConfig;
            }
        }

        $legacy = $this->decode($product->product_options ?? null);

        if ($legacy !== []) {
            $encodedLegacy = $this->encode(
                SolidQualityBusinessCardGallery::synchronizeLegacyOptions($legacy),
            );

            if ($encodedLegacy !== ($product->product_options ?? null)) {
                $updates['product_options'] = $encodedLegacy;
            }
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // The restored swatches and supplied primary images are not reverted.
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
