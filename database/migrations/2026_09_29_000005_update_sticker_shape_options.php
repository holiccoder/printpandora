<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $shapeValues = StickerProductCatalog::shapeValues();
        $shapeCodes = array_column($shapeValues, 'code');

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($shapeValues, $shapeCodes): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $options = is_array($config['options'] ?? null)
                    ? $config['options']
                    : [];
                $currentShape = is_array($options['shape'] ?? null)
                    ? $options['shape']
                    : [];
                $currentDefault = (string) ($currentShape['default'] ?? '');

                $options['shape'] = [
                    ...$currentShape,
                    'label' => 'Shape',
                    'type' => 'select',
                    'required' => true,
                    'default' => in_array($currentDefault, $shapeCodes, true)
                        ? $currentDefault
                        : 'square_corner',
                    'values' => $shapeValues,
                ];
                $config['options'] = $options;

                $this->saveConfig($product->id, $config);
            });
    }

    public function down(): void
    {
        $legacyShapeValues = [
            [
                'code' => 'square_corner',
                'label' => 'Square Corner',
                'description' => 'Clean square corners.',
                'swatch_image' => '/images/product-options/stickers/square-corner.svg',
            ],
            [
                'code' => 'die_cut',
                'label' => 'Die Cut',
                'description' => 'Cut to the outline of your artwork.',
                'swatch_image' => '/images/product-options/stickers/die-cut.svg',
            ],
        ];

        DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product) use ($legacyShapeValues): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === null) {
                    return;
                }

                $options = is_array($config['options'] ?? null)
                    ? $config['options']
                    : [];
                $shape = is_array($options['shape'] ?? null)
                    ? $options['shape']
                    : [];

                $options['shape'] = [
                    ...$shape,
                    'label' => 'Shape',
                    'type' => 'select',
                    'required' => true,
                    'default' => 'square_corner',
                    'values' => $legacyShapeValues,
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
