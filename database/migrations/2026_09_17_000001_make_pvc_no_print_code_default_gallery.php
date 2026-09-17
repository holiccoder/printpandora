<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NO_PRINT_CODE_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    /**
     * @var list<string>
     */
    private const PVC_PRODUCT_SLUGS = [
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
    ];

    public function up(): void
    {
        foreach (self::PVC_PRODUCT_SLUGS as $slug) {
            $this->updateProduct($slug);
        }
    }

    public function down(): void
    {
        // The no-print-code swatch remains the intentional default gallery image.
    }

    private function updateProduct(string $slug): void
    {
        $product = DB::table('products')
            ->where('slug', $slug)
            ->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $rules = is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [];
        $gallery = is_array($media['gallery'] ?? null)
            ? array_values($media['gallery'])
            : [];

        if ($gallery === []) {
            foreach ($rules as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                if (($rule['id'] ?? null) === 'default' || $match === []) {
                    $gallery = is_array($rule['images'] ?? null)
                        ? array_values($rule['images'])
                        : [];

                    break;
                }
            }
        }

        $gallery = $this->withNoPrintCodeImageFirst($gallery);
        $media['gallery'] = $gallery;

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
            $rule['images'] = $gallery;
            $rule['primary'] = self::NO_PRINT_CODE_IMAGE;
        }
        unset($rule);

        if (! $hasDefaultRule) {
            array_unshift($rules, [
                'id' => 'default',
                'match' => [],
                'images' => $gallery,
                'primary' => self::NO_PRINT_CODE_IMAGE,
            ]);
        }

        $media['gallery_rules'] = array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => is_array($rule),
        ));
        $config['media'] = $media;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => $this->encodeConfig($config),
            ]);
    }

    /**
     * @param  array<int, mixed>  $images
     * @return array<int, mixed>
     */
    private function withNoPrintCodeImageFirst(array $images): array
    {
        return [
            self::NO_PRINT_CODE_IMAGE,
            ...array_values(array_filter(
                $images,
                static fn (mixed $image): bool => $image !== self::NO_PRINT_CODE_IMAGE,
            )),
        ];
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
