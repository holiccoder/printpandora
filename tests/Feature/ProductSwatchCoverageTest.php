<?php

namespace Tests\Feature;

use App\Support\BusinessCardOptionCatalog;
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
        $this->assertArrayNotHasKey('paper_finish', $canonicalOptions['classic-quality-business-cards']);
        $this->assertArrayNotHasKey('print_code', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('drill', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $legacyOptions['classic-special-business-cards']);
        $this->assertArrayNotHasKey('paper_finish', $legacyOptions['classic-quality-business-cards']);
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
                    'no_print_code' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    'need_print_code' => '/images/product-options/business-cards/swatches/pvc-print-code.png',
                ],
                'drill' => [
                    'no_drilling' => '/images/product-options/business-cards/swatches/drilling/no-drilling.png',
                    'needs_drilling' => '/images/product-options/business-cards/swatches/drilling/needs-drilling.png',
                ],
            ],
            'business-cards/classic-quality-business-cards.json' => [
                'texture' => [
                    'shattered_glass_film' => '/images/product-options/business-cards/swatches/quality/shattered-glass-film.png',
                    'holographic_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
                    'starlight_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                    'holographic_star_film' => '/images/product-options/business-cards/swatches/quality/holographic-star-film.png',
                    'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
                    'matte' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
                    'gloss' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
                ],
            ],
            'business-cards/classic-solid-business-cards.json' => [
                'print_code' => [
                    'no_print_code' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    'need_print_code' => '/images/product-options/business-cards/swatches/pvc-print-code.png',
                ],
                'drill' => [
                    'no_drilling' => '/images/product-options/business-cards/swatches/drilling/no-drilling.png',
                    'needs_drilling' => '/images/product-options/business-cards/swatches/drilling/needs-drilling.png',
                ],
            ],
            'pvc-business-cards/standard-pvc-card.json' => [
                'print_code_or_signature_stripe' => [
                    'no_print_code_or_signature_stripe' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
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
