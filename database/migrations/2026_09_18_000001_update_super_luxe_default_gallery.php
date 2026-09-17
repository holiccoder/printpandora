<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_GALLERY = [
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
    ];

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', 'super-luxe-business-cards')
            ->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $rules = is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [];
        $hasDefaultRule = false;

        foreach ($rules as &$rule) {
            if (! is_array($rule)) {
                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (($rule['id'] ?? null) !== 'default' && $match !== []) {
                continue;
            }

            $hasDefaultRule = true;
            $rule['images'] = self::DEFAULT_GALLERY;
            $rule['primary'] = self::DEFAULT_GALLERY[0];
        }
        unset($rule);

        if (! $hasDefaultRule) {
            array_unshift($rules, [
                'id' => 'default',
                'match' => [],
                'images' => self::DEFAULT_GALLERY,
                'primary' => self::DEFAULT_GALLERY[0],
            ]);
        }

        $media['gallery'] = self::DEFAULT_GALLERY;
        $media['gallery_rules'] = array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => is_array($rule),
        ));
        $config['media'] = $media;
        $config['product'] = is_array($config['product'] ?? null) ? $config['product'] : [];
        $config['product']['featured_image'] = self::DEFAULT_GALLERY[0];

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'featured_image' => self::DEFAULT_GALLERY[0],
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
