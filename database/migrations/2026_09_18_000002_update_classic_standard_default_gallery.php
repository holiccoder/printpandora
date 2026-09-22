<?php

use App\Support\ClassicStandardBusinessCardGallery;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', ClassicStandardBusinessCardGallery::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $config = ClassicStandardBusinessCardGallery::synchronizeConfig(
            $this->decodeConfig($product->product_config ?? null),
        );

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'featured_image' => ClassicStandardBusinessCardGallery::DEFAULT_GALLERY[0],
                'product_config' => $this->encodeConfig($config),
            ]);
    }

    public function down(): void
    {
        // The replacement gallery is intentionally not reverted.
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConfig(mixed $encoded): array
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
     * @param  array<string, mixed>  $config
     */
    private function encodeConfig(array $config): string
    {
        return json_encode(
            $config,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
