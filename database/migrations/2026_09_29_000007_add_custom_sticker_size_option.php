<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sizeValues = StickerProductCatalog::sizeValues();

        $this->updateStickerSizeValues(
            $sizeValues,
            array_column($sizeValues, 'code'),
        );
    }

    public function down(): void
    {
        $legacySizeValues = array_values(array_filter(
            StickerProductCatalog::sizeValues(),
            static fn (array $size): bool => ($size['code'] ?? null) !== 'custom',
        ));

        $this->updateStickerSizeValues(
            $legacySizeValues,
            array_column($legacySizeValues, 'code'),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $sizeValues
     * @param  list<string>  $sizeCodes
     */
    private function updateStickerSizeValues(array $sizeValues, array $sizeCodes): void
    {
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
