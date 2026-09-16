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
    private const PVC_FINISH_ORDER = ['matte', 'gloss', 'frosted'];

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

    /**
     * @var array<string, array{label: string, description: string}>
     */
    private const PVC_FINISH_METADATA = [
        'matte' => [
            'label' => 'Matte',
            'description' => 'Smooth, non-reflective matte finish.',
        ],
        'gloss' => [
            'label' => 'Gloss',
            'description' => 'Shiny and highly reflective gloss finish.',
        ],
        'frosted' => [
            'label' => 'Frosted Glass',
            'description' => 'A translucent frosted-glass finish with a soft, elegant look.',
        ],
    ];

    public function up(): void
    {
        foreach (array_keys(self::PVC_FINISH_IMAGES) as $slug) {
            $this->updateProduct($slug);
        }
    }

    public function down(): void
    {
        // The finish assets are a replacement for historical paths and are
        // intentionally not reverted by a down migration.
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
        $config['options'] = $this->withFinishOptions(
            is_array($config['options'] ?? null) ? $config['options'] : [],
            self::PVC_FINISH_IMAGES[$slug],
        );

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

        $gallery = $this->defaultGallery($gallery, self::PVC_FINISH_IMAGES[$slug]);
        $media['gallery'] = $gallery;

        $finishRuleIds = array_map(
            static fn (string $finish): string => "{$finish}_gallery",
            self::PVC_FINISH_ORDER,
        );
        $rules = array_values(array_filter(
            $rules,
            function (mixed $rule) use ($finishRuleIds): bool {
                if (! is_array($rule)) {
                    return false;
                }

                if (in_array((string) ($rule['id'] ?? ''), $finishRuleIds, true)) {
                    return false;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return ! (
                    count($match) === 1
                    && array_key_exists('paper_finish', $match)
                    && in_array((string) $match['paper_finish'], self::PVC_FINISH_ORDER, true)
                );
            },
        ));

        $hasDefaultRule = false;

        foreach ($rules as &$rule) {
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if ($match !== [] && ($rule['id'] ?? null) !== 'default') {
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

        $media['gallery_rules'] = [
            ...$rules,
            ...$this->finishGalleryRules($slug),
        ];
        $config['media'] = $media;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => $this->encodeConfig($config),
            ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, string>  $finishImages
     * @return array<string, mixed>
     */
    private function withFinishOptions(array $options, array $finishImages): array
    {
        $group = is_array($options['paper_finish'] ?? null)
            ? $options['paper_finish']
            : [];
        $existingValues = is_array($group['values'] ?? null)
            ? $group['values']
            : [];
        $existingByCode = [];

        foreach ($existingValues as $value) {
            if (! is_array($value) || ! is_string($value['code'] ?? null)) {
                continue;
            }

            $existingByCode[$value['code']] = $value;
        }

        $values = [];

        foreach (self::PVC_FINISH_ORDER as $code) {
            $value = is_array($existingByCode[$code] ?? null)
                ? $existingByCode[$code]
                : [
                    'code' => $code,
                    ...self::PVC_FINISH_METADATA[$code],
                ];
            $value['code'] = $code;
            $value['swatch_image'] = $finishImages[$code];
            $values[] = $value;
        }

        $group['label'] ??= 'Paper Finish';
        $group['type'] ??= 'select';
        $group['required'] ??= true;
        $group['default'] ??= 'matte';
        $group['values'] = $values;
        $options['paper_finish'] = $group;

        return $options;
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
     * @return array<int, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    private function finishGalleryRules(string $slug): array
    {
        $finishImages = self::PVC_FINISH_IMAGES[$slug];
        $rules = [];

        foreach (self::PVC_FINISH_ORDER as $finish) {
            $primary = $finishImages[$finish];
            $rules[] = [
                'id' => "{$finish}_gallery",
                'match' => ['paper_finish' => $finish],
                'images' => [
                    $primary,
                    '/images/products/pvc/pvc-02.jpg',
                    '/images/products/pvc/pvc-03.jpg',
                    '/images/products/pvc/pvc-04.jpg',
                ],
                'primary' => $primary,
            ];
        }

        return $rules;
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
