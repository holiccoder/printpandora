<?php

namespace Tests\Feature;

use App\Support\BusinessCardOptionCatalog;
use App\Support\SolidQualityBusinessCardGallery;
use App\Support\StandardQualityBusinessCardGallery;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductSwatchCoverageTest extends TestCase
{
    /**
     * These are the option groups rendered as storefront choices. Raw paper
     * type/thickness data is intentionally not included because it is not
     * rendered as a storefront option.
     *
     * @var list<string>
     */
    private const USER_FACING_GROUPS = [
        'sizes',
        'corners',
        'paper_finish',
        'special_finish',
        'hot_foil',
        'print_code',
        'drill',
        'texture',
        'thickness',
        'print_code_or_signature_stripe',
        'print_code_or_magnetic_stripe',
        'finish',
    ];

    public function test_active_and_legacy_storefront_sources_have_complete_existing_swatches(): void
    {
        $failures = [];
        $legacyOptions = [];
        $legacySourceOptions = [];

        foreach (File::allFiles(base_path('content/product-options')) as $file) {
            if (strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $payload = json_decode(
                File::get($file->getPathname()),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $slug = data_get($payload, 'product.slug')
                ?: pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : $payload;
            $relativePath = str_replace(
                '\\',
                '/',
                substr($file->getPathname(), strlen(base_path('content/product-options')) + 1),
            );

            $legacyOptions[$slug] = $options;
            $legacySourceOptions[$relativePath] = $options;
            $this->collectSwatchFailures($options, "legacy {$file->getPathname()}", $failures);
        }

        $canonicalProducts = json_decode(
            File::get(database_path('seeders/data/products.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $canonicalOptions = [];

        foreach ($canonicalProducts as $product) {
            $slug = (string) ($product['slug'] ?? 'unknown-product');

            if (! is_string($product['product_config'] ?? null) || trim($product['product_config']) === '') {
                continue;
            }

            $config = json_decode(
                (string) ($product['product_config'] ?? ''),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $options = is_array($config['options'] ?? null) ? $config['options'] : [];

            $canonicalOptions[$slug] = $options;
            $this->collectSwatchFailures($options, "canonical {$slug}", $failures);
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));

        $this->assertArrayNotHasKey('print_code', $canonicalOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('drill', $canonicalOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $canonicalOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $canonicalOptions['standard-quality-business-cards']);
        $this->assertArrayNotHasKey('print_code', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('drill', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $legacyOptions['standard-quality-business-cards']);
        $this->assertArrayNotHasKey('print_code', $canonicalOptions['classic-standard-business-cards']);
        $this->assertArrayNotHasKey('drill', $canonicalOptions['classic-standard-business-cards']);
        $this->assertArrayNotHasKey('print_code', $legacyOptions['classic-standard-business-cards']);
        $this->assertArrayNotHasKey('drill', $legacyOptions['classic-standard-business-cards']);

        foreach ($canonicalOptions as $slug => $options) {
            $this->assertArrayNotHasKey(
                'special_finish_on_sides',
                $options,
                $slug.' canonical options',
            );
        }

        foreach ($legacyOptions as $slug => $options) {
            $this->assertArrayNotHasKey(
                'special_finish_on_sides',
                $options,
                $slug.' legacy options',
            );
        }

        $expectedLegacySwatches = [
            'business-cards/classic-business-cards.json' => [
                'print_code' => [
                    'need_print_code' => '/images/product-options/business-cards/swatches/pvc-print-code.png',
                ],
                'drill' => [
                    'needs_drilling' => '/images/product-options/business-cards/swatches/drilling/needs-drilling.png',
                ],
            ],
            'business-cards/standard-quality-business-cards.json' => [
                'texture' => [
                    'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                    'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
                    'starlight_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                    'holographic_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
                    'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
                ],
                'uv_finish' => [
                    'single_side_uv' => '/images/products/standard-quality-business-cards/3d-uv.png',
                    'both_sides_uv' => '/images/products/standard-quality-business-cards/3d-uv.png',
                ],
            ],
            'business-cards/solid-quality-business-cards.json' => [
                'paper_finish' => [
                    'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                    'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
                    'starry_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                    'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
                    'holo_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
                ],
                'uv_finish' => [
                    'single_side_uv' => '/images/product-options/uv-swatch.png',
                    'both_sides_uv' => '/images/product-options/uv-swatch.png',
                ],
                'print_code' => [
                    'need_print_code' => '/images/product-options/business-cards/swatches/pvc-print-code.png',
                ],
                'drill' => [
                    'needs_drilling' => '/images/product-options/business-cards/swatches/drilling/needs-drilling.png',
                ],
            ],
            'pvc-business-cards/standard-pvc-card.json' => [
                'print_code_or_signature_stripe' => [
                    'print_code' => '/images/products/pvc/pvc-print-code.png',
                    'signature_stripe' => '/images/products/pvc/pvc-signature-stripe.png',
                ],
            ],
        ];

        foreach ($expectedLegacySwatches as $sourcePath => $groups) {
            foreach ($groups as $groupKey => $expectedValues) {
                $values = $this->optionValues($legacySourceOptions[$sourcePath], $groupKey);

                $this->assertSame(array_keys($expectedValues), array_column($values, 'code'));
                $this->assertSame(array_values($expectedValues), array_column($values, 'swatch_image'));
            }
        }

        $this->assertFileExists(
            public_path('images/products/standard-quality-business-cards/3d-uv.png'),
        );

        $expectedColdFoilSwatches = [
            'cold_matte_gold' => '/images/product-options/business-cards/swatches/cold/matte-gold.png',
            'cold_matte_silver' => '/images/product-options/business-cards/swatches/cold/matte-silver.png',
            'cold_bright_gold' => '/images/product-options/business-cards/swatches/cold/bright-gold.png',
            'cold_bright_silver' => '/images/product-options/business-cards/swatches/cold/bright-silver.png',
            'cold_red_gold' => '/images/product-options/business-cards/swatches/cold/red-gold.png',
            'cold_green_gold' => '/images/product-options/business-cards/swatches/cold/green-gold.png',
            'cold_blue_gold' => '/images/product-options/business-cards/swatches/cold/blue-gold.png',
        ];

        foreach ($expectedColdFoilSwatches as $code => $image) {
            $value = collect($this->optionValues(
                $legacySourceOptions['business-cards/solid-quality-business-cards.json'],
                'special_finish',
            ))->firstWhere('code', $code);

            $this->assertSame($image, $value['swatch_image'] ?? null, $code);
        }
    }

    public function test_shared_swatch_normalization_fills_only_blank_values(): void
    {
        $normalized = BusinessCardOptionCatalog::normalizeSharedSwatchImages([
            'print_code' => [
                'values' => [
                    [
                        'code' => 'no_print_code',
                        'swatch_image' => '/images/keep-this-existing-swatch.png',
                    ],
                    [
                        'code' => 'need_print_code',
                        'swatch_image' => '',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            '/images/keep-this-existing-swatch.png',
            data_get($normalized, 'print_code.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/pvc-print-code.png',
            data_get($normalized, 'print_code.values.1.swatch_image'),
        );
    }

    public function test_cotton_thickness_and_paper_sample_contract_has_complete_assets(): void
    {
        $options = BusinessCardOptionCatalog::normalize(
            'basic-cotton-business-card',
            [],
        );

        $this->assertIsArray($options);
        $this->assertSame(
            ['300_360g', '360_450g', '450_700g'],
            data_get($options, 'thickness.values.*.code'),
        );

        $textureValues = data_get($options, 'texture.values', []);
        $this->assertCount(47, $textureValues);
        $this->assertSame(
            [13, 13, 21],
            collect($textureValues)
                ->groupBy('thickness_code')
                ->map(fn ($values): int => $values->count())
                ->values()
                ->all(),
        );

        foreach ($textureValues as $value) {
            $image = (string) ($value['swatch_image'] ?? '');
            $this->assertStringStartsWith(
                '/images/products/cotton/paper-samples/',
                $image,
            );
            $this->assertFileExists(public_path(ltrim($image, '/')));
            $this->assertFileExists(
                public_path(preg_replace('/\.png$/i', '.webp', ltrim($image, '/'))),
            );

            $colorSwatch = (string) ($value['color_swatch_image'] ?? '');
            $this->assertStringStartsWith(
                '/images/products/cotton/paper-samples/swatches/',
                $colorSwatch,
            );
            $this->assertStringEndsWith('.webp', $colorSwatch);
            $this->assertFileExists(public_path(ltrim($colorSwatch, '/')));
        }
    }

    public function test_cotton_special_finish_swatches_have_complete_assets(): void
    {
        $options = BusinessCardOptionCatalog::normalize(
            'basic-cotton-business-card',
            [],
        );

        $expectedImages = [
            '/images/products/cotton/special-finishes/laser-diagram.png',
            '/images/products/cotton/special-finishes/edge-coloring-diagram.png',
            '/images/products/cotton/special-finishes/double-mounting-diagram.png',
            '/images/products/cotton/special-finishes/custom-die-cut-diagram.png',
            '/images/products/cotton/special-finishes/emboss-deboss-diagram.png',
            '/images/products/cotton/special-finishes/deboss-diagram.png',
        ];

        $this->assertSame(
            $expectedImages,
            data_get($options, 'special_finish.values.*.swatch_image'),
        );

        foreach ($expectedImages as $image) {
            $sourcePath = public_path(ltrim($image, '/'));

            $this->assertFileExists($sourcePath);
            $this->assertFileExists(
                preg_replace('/\.png$/i', '.webp', $sourcePath),
            );
        }

        $expectedPrimaryImages = [
            '/images/products/cotton/special-finishes/laser.png',
            '/images/products/cotton/special-finishes/edge-coloring.png',
            '/images/products/cotton/special-finishes/double-mounting.png',
            '/images/products/cotton/special-finishes/custom-die-cut.png',
            '/images/products/cotton/special-finishes/emboss.png',
            '/images/products/cotton/special-finishes/deboss.png',
        ];

        foreach ($expectedPrimaryImages as $image) {
            $sourcePath = public_path(ltrim($image, '/'));

            $this->assertFileExists($sourcePath);
            $this->assertFileExists(
                preg_replace('/\.png$/i', '.webp', $sourcePath),
            );
        }
    }

    public function test_cotton_hot_foil_swatches_have_complete_assets(): void
    {
        $slugs = [
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card',
        ];

        $expectedCodes = [
            'black_gold',
            'blue_gold',
            'bright_gold',
            'bright_silver',
            'green_gold',
            'matte_gold',
            'matte_silver',
            'red_gold',
            'rose_gold',
            'aged_gold',
            'muted_purple_gold',
        ];

        foreach ($slugs as $slug) {
            $options = BusinessCardOptionCatalog::normalize($slug, []);

            $this->assertSame($expectedCodes, data_get($options, 'hot_foil.values.*.code'));
            $this->assertSame('Hot Foil', data_get($options, 'hot_foil.label'));
            $this->assertSame('multi_select', data_get($options, 'hot_foil.type'));
            $this->assertFalse((bool) data_get($options, 'hot_foil.required'));

            foreach (data_get($options, 'hot_foil.values.*.swatch_image', []) as $image) {
                $this->assertFileExists(public_path(ltrim($image, '/')));
            }
        }
    }

    public function test_standard_quality_product_assets_have_webp_derivatives(): void
    {
        $paths = [
            ...StandardQualityBusinessCardGallery::DEFAULT_GALLERY,
            '/images/products/standard-quality-business-cards/standard-matte-square.png',
            '/images/products/standard-quality-business-cards/standard-matte-rounded.png',
            '/images/products/standard-quality-business-cards/standard-gloss-rounded.png',
            '/images/products/standard-quality-business-cards/standard-gloss-square.png',
            '/images/products/standard-quality-business-cards/texture/starlight-film.png',
            '/images/products/standard-quality-business-cards/texture/holographic-film.png',
            '/images/products/standard-quality-business-cards/texture/soft-touch-film.png',
            '/images/products/standard-quality-business-cards/3d-uv.png',
            ...array_values(StandardQualityBusinessCardGallery::COLD_FOIL_IMAGES),
        ];

        foreach (array_unique($paths) as $path) {
            $absolutePath = public_path(ltrim($path, '/'));

            $this->assertFileExists($absolutePath);
            $this->assertFileExists(
                preg_replace('/\.png$/i', '.webp', $absolutePath),
            );
        }
    }

    public function test_solid_quality_product_assets_have_webp_derivatives(): void
    {
        $paths = [
            ...SolidQualityBusinessCardGallery::DEFAULT_GALLERY,
            '/images/products/solid-quality-business-cards/standard-matte-square.png',
            '/images/products/solid-quality-business-cards/standard-matte-rounded.png',
            '/images/products/solid-quality-business-cards/standard-gloss-rounded.png',
            '/images/products/solid-quality-business-cards/texture/starlight-film.png',
            '/images/products/solid-quality-business-cards/texture/laser-film.png',
            '/images/products/solid-quality-business-cards/texture/soft-touch-film.png',
            '/images/products/solid-quality-business-cards/texture/starlight-film-primary.png',
            '/images/products/solid-quality-business-cards/texture/laser-film-primary.png',
            '/images/products/solid-quality-business-cards/texture/soft-touch-film-primary.png',
            ...array_values(SolidQualityBusinessCardGallery::COLD_FOIL_IMAGES),
        ];

        foreach (array_unique($paths) as $path) {
            $absolutePath = public_path(ltrim($path, '/'));

            $this->assertFileExists($absolutePath);
            $this->assertFileExists(
                preg_replace('/\.png$/i', '.webp', $absolutePath),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $failures
     */
    private function collectSwatchFailures(array $options, string $source, array &$failures): void
    {
        foreach (self::USER_FACING_GROUPS as $groupKey) {
            if (! array_key_exists($groupKey, $options)) {
                continue;
            }

            $group = $options[$groupKey];
            $values = is_array($group) && array_key_exists('values', $group)
                ? $group['values']
                : $group;

            if (! is_array($values)) {
                $failures[] = "{$source}: {$groupKey} does not contain a value list";

                continue;
            }

            foreach ($values as $index => $value) {
                if (! is_array($value)) {
                    $failures[] = "{$source}: {$groupKey}[{$index}] is not an option value";

                    continue;
                }

                $swatch = $value['swatch_image'] ?? null;
                if (! is_string($swatch) || trim($swatch) === '') {
                    $code = $value['code'] ?? 'unknown';
                    $failures[] = "{$source}: {$groupKey}[{$index}] ({$code}) has no swatch_image";

                    continue;
                }

                if (str_starts_with($swatch, '/images/') && ! is_file(public_path(ltrim($swatch, '/')))) {
                    $failures[] = "{$source}: {$groupKey}[{$index}] references missing {$swatch}";
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    private function optionValues(array $options, string $groupKey): array
    {
        $group = $options[$groupKey] ?? [];

        if (! is_array($group)) {
            return [];
        }

        $values = array_key_exists('values', $group) ? $group['values'] : $group;

        if (! is_array($values)) {
            return [];
        }

        $optionValues = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                $optionValues[] = $value;
            }
        }

        return $optionValues;
    }
}
