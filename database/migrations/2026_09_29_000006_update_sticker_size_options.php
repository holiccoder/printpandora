<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sizeValues = StickerProductCatalog::sizeValues();
        $sizeCodes = array_column($sizeValues, 'code');

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($sizeValues, $sizeCodes): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $options = is_array($config['options'] ?? null)
                    ? $config['options']
                    : [];
                $currentSize = is_array($options['sizes'] ?? null)
                    ? $options['sizes']
                    : [];
                $currentDefault = (string) ($currentSize['default'] ?? '');

                $options['sizes'] = [
                    ...$currentSize,
                    'label' => 'Size',
                    'type' => 'select',
                    'required' => true,
                    'default' => in_array($currentDefault, $sizeCodes, true)
                        ? $currentDefault
                        : $sizeValues[0]['code'],
                    'values' => $sizeValues,
                ];
                $config['options'] = $options;

                $this->saveConfig($product->id, $config);
            });
    }

    public function down(): void
    {
        $legacySizeValues = [
            [
                'code' => '2x2',
                'label' => '2 x 2 in',
                'description' => '2 x 2 inches',
                'width' => '2.00',
                'height' => '2.00',
                'area_sq_m' => StickerProductCatalog::areaInSquareMetres(2, 2),
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
            [
                'code' => '3x3',
                'label' => '3 x 3 in',
                'description' => '3 x 3 inches',
                'width' => '3.00',
                'height' => '3.00',
                'area_sq_m' => StickerProductCatalog::areaInSquareMetres(3, 3),
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
            [
                'code' => '4x4',
                'label' => '4 x 4 in',
                'description' => '4 x 4 inches',
                'width' => '4.00',
                'height' => '4.00',
                'area_sq_m' => StickerProductCatalog::areaInSquareMetres(4, 4),
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
            [
                'code' => '5x5',
                'label' => '5 x 5 in',
                'description' => '5 x 5 inches',
                'width' => '5.00',
                'height' => '5.00',
                'area_sq_m' => StickerProductCatalog::areaInSquareMetres(5, 5),
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
            [
                'code' => 'custom',
                'label' => 'Custom Size',
                'description' => '18 x 18 mm minimum; up to 430 x 301 mm.',
                'min_width' => '0.71',
                'max_width' => '16.93',
                'min_height' => '0.71',
                'max_height' => '11.85',
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
        ];

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($legacySizeValues): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $options = is_array($config['options'] ?? null)
                    ? $config['options']
                    : [];
                $size = is_array($options['sizes'] ?? null)
                    ? $options['sizes']
                    : [];

                $options['sizes'] = [
                    ...$size,
                    'label' => 'Size',
                    'type' => 'select',
                    'required' => true,
                    'default' => '2x2',
                    'values' => $legacySizeValues,
                ];
                $config['options'] = $options;

                $this->saveConfig($product->id, $config);
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function saveConfig(int $productId, array $config): void
    {
        DB::table('products')
            ->where('id', $productId)
            ->update([
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                'updated_at' => now(),
            ]);
    }
};
