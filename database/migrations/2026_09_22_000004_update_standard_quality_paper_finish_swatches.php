<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'standard-quality-business-cards';

    /**
     * @var array<string, string>
     */
    private const SWATCH_IMAGES = [
        'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
        'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
        'starlight_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
        'holographic_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
        'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
    ];

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
        [$config, $configChanged] = $this->updateOptionValues(
            $config,
            ['options', 'texture', 'values'],
        );

        if ($configChanged) {
            $updates['product_config'] = $this->encode($config);
        }

        $legacy = $this->decode($product->product_options ?? null);
        [$legacy, $legacyChanged] = $this->updateOptionValues($legacy, ['texture']);

        if ($legacyChanged) {
            $updates['product_options'] = $this->encode($legacy);
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // The shared paper-finish swatches are not reverted on rollback.
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $path
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateOptionValues(array $payload, array $path): array
    {
        $values = $payload;

        foreach ($path as $segment) {
            if (! is_array($values) || ! array_key_exists($segment, $values)) {
                return [$payload, false];
            }

            $values = $values[$segment];
        }

        if (! is_array($values)) {
            return [$payload, false];
        }

        $changed = false;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if (! isset(self::SWATCH_IMAGES[$code]) || ($value['swatch_image'] ?? null) === self::SWATCH_IMAGES[$code]) {
                continue;
            }

            $value['swatch_image'] = self::SWATCH_IMAGES[$code];
            $changed = true;
        }
        unset($value);

        if (! $changed) {
            return [$payload, false];
        }

        $target =& $payload;

        foreach ($path as $segment) {
            $target =& $target[$segment];
        }

        $target = array_values($values);
        unset($target);

        return [$payload, true];
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
