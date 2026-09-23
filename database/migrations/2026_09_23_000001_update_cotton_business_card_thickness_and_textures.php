<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->get()
            ->each(function (object $product): void {
                $updates = [];
                $config = $this->decode($product->product_config ?? null);

                if ($config !== []) {
                    $options = BusinessCardOptionCatalog::normalize(
                        (string) $product->slug,
                        is_array($config['options'] ?? null) ? $config['options'] : [],
                    );

                    if ($options !== null) {
                        $config['options'] = $options;
                    }

                    $config['media'] = is_array($config['media'] ?? null)
                        ? $config['media']
                        : [];
                    $gallery = is_array($config['media']['gallery'] ?? null)
                        ? array_values($config['media']['gallery'])
                        : [];
                    $galleryRules = is_array($config['media']['gallery_rules'] ?? null)
                        ? $config['media']['gallery_rules']
                        : [];

                    if ($gallery !== []) {
                        $hasDefault = false;

                        foreach ($galleryRules as &$rule) {
                            if (! is_array($rule)) {
                                continue;
                            }

                            $match = is_array($rule['match'] ?? null)
                                ? $rule['match']
                                : [];

                            if (($rule['id'] ?? null) !== 'default' && $match !== []) {
                                continue;
                            }

                            if ($hasDefault) {
                                $rule = null;

                                continue;
                            }

                            $hasDefault = true;
                            $rule['id'] = 'default';
                            $rule['match'] = [];
                            $rule['images'] = $gallery;
                            $rule['primary'] = $gallery[0];
                        }
                        unset($rule);

                        $galleryRules = array_values(array_filter(
                            $galleryRules,
                            static fn (mixed $rule): bool => is_array($rule),
                        ));

                        if (! $hasDefault) {
                            array_unshift($galleryRules, [
                                'id' => 'default',
                                'match' => [],
                                'images' => $gallery,
                                'primary' => $gallery[0],
                            ]);
                        }
                    }

                    $config['media']['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                        $galleryRules,
                    );
                    $updates['product_config'] = $this->encode($config);
                }

                $legacy = $this->decode($product->product_options ?? null);

                if ($legacy !== []) {
                    $legacyOptions = BusinessCardOptionCatalog::normalize(
                        (string) $product->slug,
                        $legacy,
                    );

                    if ($legacyOptions !== null) {
                        foreach (['thickness', 'texture'] as $groupKey) {
                            $values = $legacyOptions[$groupKey]['values'] ?? null;

                            if (! is_array($values)) {
                                continue;
                            }

                            $legacy[$groupKey] = array_map(
                                static fn (array $value): array => [
                                    'name' => $value['label'] ?? '',
                                    'code' => $value['code'] ?? '',
                                    'description' => $value['description'] ?? '',
                                    'swatch_image' => $value['swatch_image'] ?? '',
                                    ...array_intersect_key(
                                        $value,
                                        array_flip([
                                            'thickness_code',
                                            'texture_code',
                                            'texture_label',
                                            'color_code',
                                            'color_label',
                                        ]),
                                    ),
                                ],
                                array_values(array_filter($values, is_array(...))),
                            );
                        }

                        $updates['product_options'] = $this->encode($legacy);
                    }
                }

                if ($updates !== []) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        // The cotton thickness and paper-sample contract is not reverted.
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
