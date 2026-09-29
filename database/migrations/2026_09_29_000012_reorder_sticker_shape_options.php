<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateStickerShapeValues(StickerProductCatalog::shapeValues());
    }

    public function down(): void
    {
        $this->updateStickerShapeValues([
            [
                'code' => 'die_cut',
                'label' => 'Any Shape',
                'description' => 'Cut to any custom outline of your artwork.',
                'swatch_image' => '/images/product-options/stickers/shapes/any-shape.png',
            ],
            [
                'code' => 'round',
                'label' => 'Round',
                'description' => 'A clean circular sticker shape.',
                'swatch_image' => '/images/product-options/stickers/shapes/round.png',
            ],
            [
                'code' => 'rounded_corner',
                'label' => 'Rounded Corner',
                'description' => 'A rectangle with softly rounded corners.',
                'swatch_image' => '/images/product-options/stickers/shapes/rounded-corner.png',
            ],
            [
                'code' => 'square_corner',
                'label' => 'Square Corner',
                'description' => 'A rectangle with clean square corners.',
                'swatch_image' => '/images/product-options/stickers/shapes/square-corner.png',
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $shapeValues
     */
    private function updateStickerShapeValues(array $shapeValues): void
    {
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
                        : $shapeValues[0]['code'],
                    'values' => $shapeValues,
                ];
                $config['options'] = $options;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'product_config' => json_encode(
                            $config,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ),
                        'updated_at' => now(),
                    ]);
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
};
