<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NO_PRINT_CODE_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    /**
     * @var array<int, string>
     */
    private const PVC_BASE_GALLERY = [
        '/images/products/pvc/pvc-01.jpg',
        '/images/products/pvc/pvc-02.jpg',
        '/images/products/pvc/pvc-03.jpg',
        '/images/products/pvc/pvc-04.jpg',
    ];

    /**
     * @var array<int, string>
     */
    private const PVC_DEFAULT_FINISH_ORDER = ['matte', 'gloss', 'frosted'];

    /**
     * @var array<string, array<string, string>>
     */
    private const PVC_FINISH_IMAGES = [
        'basic-pvc-card' => [
            'matte' => '/images/products/pvc/basic-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/basic-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/basic-pvc-card-frosted.png',
        ],
        'standard-pvc-card' => [
            'matte' => '/images/products/pvc/standard-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/standard-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/standard-pvc-card-frosted.png',
        ],
        'premium-pvc-card' => [
            'matte' => '/images/products/pvc/premium-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/premium-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/premium-pvc-card-frosted.png',
        ],
    ];

    public function up(): void
    {
        foreach (self::PVC_FINISH_IMAGES as $slug => $finishImages) {
            $this->updateProduct($slug, $finishImages);
        }
    }

    public function down(): void
    {
        // The default gallery order is intentionally not reverted.
    }

    /**
     * @param  array<string, string>  $finishImages
     */
    private function updateProduct(string $slug, array $finishImages): void
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

        if ($gallery === []) {
            $gallery = self::PVC_BASE_GALLERY;
        }

        $gallery = $this->defaultGallery($gallery, $finishImages);
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

        $media['gallery_rules'] = $rules;
        $config['media'] = $media;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => $this->encodeConfig($config),
            ]);
    }

    /**
     * @param  array<int, mixed>  $gallery
     * @param  array<string, string>  $finishImages
     * @return array<int, string>
     */
    private function defaultGallery(array $gallery, array $finishImages): array
    {
        $imagesToRemove = [self::NO_PRINT_CODE_IMAGE, ...array_values($finishImages)];
        $baseGallery = array_values(array_filter(
            $gallery,
            static fn (mixed $image): bool => is_string($image)
                && ! in_array($image, $imagesToRemove, true),
        ));

        return [
            self::NO_PRINT_CODE_IMAGE,
            ...$baseGallery,
            ...array_values(array_map(
                static fn (string $finish): string => $finishImages[$finish],
                self::PVC_DEFAULT_FINISH_ORDER,
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
