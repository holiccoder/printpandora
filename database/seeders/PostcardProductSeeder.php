<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Support\CardsAndPostcardsProductImageCatalog;
use App\Support\PostcardProductCatalog;
use App\Support\PostcardProductSwatchCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostcardProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = ProductCategory::query()->updateOrCreate(
                ['slug' => PostcardProductCatalog::CATEGORY_SLUG],
                ['name' => 'Cards & Postcards', 'parent_id' => null],
            );
            $configuration = app(ProductConfigurationService::class);

            foreach (PostcardProductCatalog::slugs() as $slug) {
                $definition = PostcardProductCatalog::definition($slug);

                if ($definition === null) {
                    continue;
                }

                $reference = Product::query()
                    ->where('slug', $definition['reference'])
                    ->first();

                if ($reference === null) {
                    continue;
                }

                $subtitle = $this->replaceBusinessCardCopy($reference->subtitle);
                $description = $this->replaceBusinessCardCopy($reference->description);
                $descriptionTitle = $this->replaceBusinessCardCopy($reference->description_title);
                $bulletPoints = $this->replaceBusinessCardCopy($reference->bullet_points ?? []);
                $metaDescription = $this->replaceBusinessCardCopy($reference->meta_description);
                $config = $this->postcardConfig(
                    $configuration->canonicalConfig($reference),
                    $definition,
                    $slug,
                    $subtitle,
                    $description,
                    $descriptionTitle,
                    $bulletPoints,
                    $metaDescription,
                );
                $gallery = $definition['gallery'];
                $featuredImage = $definition['featured_image'] ?? $gallery[0];

                $product = Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'subtitle' => $subtitle,
                        'description_title' => $descriptionTitle,
                        'description' => $description,
                        'bullet_points' => $bulletPoints,
                        'product_options' => $this->replaceBusinessCardCopy($reference->product_options),
                        'product_config' => $config,
                        'price_line' => null,
                        'meta_description' => $metaDescription,
                        'featured_image' => $featuredImage,
                        'product_category_id' => $category->getKey(),
                        'is_active' => true,
                        'weight' => $reference->weight,
                    ],
                );

                $configuration->syncProductProjection($product->fresh());
            }
        });
    }

    /**
     * @param  array<string, mixed>  $referenceConfig
     * @param  array<string, mixed>  $definition
     * @param  array<int, string>|null  $bulletPoints
     * @return array<string, mixed>
     */
    private function postcardConfig(
        array $referenceConfig,
        array $definition,
        string $slug,
        ?string $subtitle,
        ?string $description,
        ?string $descriptionTitle,
        ?array $bulletPoints,
        ?string $metaDescription,
    ): array {
        $config = $this->replaceBusinessCardCopy($referenceConfig);
        $product = is_array($config['product'] ?? null) ? $config['product'] : [];
        $product = array_replace($product, [
            'name' => $definition['name'],
            'slug' => $slug,
            'subtitle' => $subtitle,
            'description' => $description,
            'description_title' => $descriptionTitle,
            'bullet_points' => $bulletPoints ?? [],
            'meta_description' => $metaDescription,
            'featured_image' => $definition['featured_image'] ?? $definition['gallery'][0],
        ]);
        $config['product'] = $product;
        $config['options'] = $this->postcardOptions(
            is_array($config['options'] ?? null) ? $config['options'] : [],
            (float) $definition['area_sq_m'],
        );
        $config['pricing'] = PostcardProductCatalog::pricingFor($slug);

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $media['gallery'] = $definition['gallery'];
        $media['gallery_rules'] = [[
            'id' => 'default',
            'match' => [],
            'images' => $definition['gallery'],
            'primary' => $definition['gallery'][0],
        ]];
        $config['media'] = $media;

        $config = CardsAndPostcardsProductImageCatalog::synchronizePostcardConfig($config, $slug);

        return PostcardProductSwatchCatalog::synchronizeConfig($config, $slug);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function postcardOptions(array $options, float $standardArea): array
    {
        $sizeGroup = is_array($options['sizes'] ?? null) ? $options['sizes'] : [];
        $sizeValues = is_array($sizeGroup['values'] ?? null) ? $sizeGroup['values'] : [];
        $standardDimensions = null;

        foreach ($sizeValues as $value) {
            if (
                is_array($value)
                && strtolower((string) ($value['code'] ?? '')) === 'standard'
                && is_numeric($value['width'] ?? null)
                && is_numeric($value['height'] ?? null)
            ) {
                $standardDimensions = (float) $value['width'] * (float) $value['height'];
                break;
            }
        }

        foreach ($sizeValues as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = strtolower((string) ($value['code'] ?? ''));

            if ($code === 'standard') {
                $value['area_sq_m'] = $standardArea;
            } elseif (
                $code === 'square'
                && $standardDimensions !== null
                && $standardDimensions > 0
                && is_numeric($value['width'] ?? null)
                && is_numeric($value['height'] ?? null)
            ) {
                $squareDimensions = (float) $value['width'] * (float) $value['height'];
                $value['area_sq_m'] = round(
                    $standardArea * ($squareDimensions / $standardDimensions),
                    8,
                );
            }
        }
        unset($value);

        if ($sizeValues !== []) {
            $sizeGroup['values'] = array_values($sizeValues);
            $options['sizes'] = $sizeGroup;
        }

        if (! is_array($options['uv_finish'] ?? null)) {
            $options['uv_finish'] = [
                'label' => 'UV Finish',
                'type' => 'select',
                'required' => false,
                'default' => null,
                'values' => [
                    [
                        'code' => 'single_side_uv',
                        'label' => 'Single side UV',
                        'description' => 'A regular UV finish on one side.',
                    ],
                    [
                        'code' => 'both_sides_uv',
                        'label' => 'Both sides UV',
                        'description' => 'A regular UV finish on both sides.',
                    ],
                ],
            ];
        }

        $specialFinish = is_array($options['special_finish'] ?? null)
            ? $options['special_finish']
            : [
                'label' => 'Special Finish',
                'type' => 'multi_select',
                'required' => false,
                'default' => [],
                'values' => [],
            ];
        $specialValues = is_array($specialFinish['values'] ?? null)
            ? $specialFinish['values']
            : [];
        $removedCodes = ['3d_uv', 'custom_die_cut'];
        $specialValues = array_values(array_filter(
            $specialValues,
            static fn (mixed $value): bool => ! is_array($value)
                || ! in_array(strtolower((string) ($value['code'] ?? '')), $removedCodes, true),
        ));
        $existingCodes = array_values(array_filter(array_map(
            static fn (mixed $value): string => is_array($value)
                ? strtolower((string) ($value['code'] ?? ''))
                : '',
            $specialValues,
        )));

        foreach ([
            [
                'code' => 'cold_red_gold',
                'label' => 'Cold Red Gold',
                'description' => 'Vibrant cold gold foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/red-gold.png',
            ],
            [
                'code' => 'cold_blue_gold',
                'label' => 'Cold Blue Gold',
                'description' => 'Elegant cold blue foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/blue-gold.png',
            ],
            [
                'code' => 'cold_bright_gold',
                'label' => 'Cold Bright Gold',
                'description' => 'Glistening cold gold foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/bright-gold.png',
            ],
            [
                'code' => 'cold_bright_silver',
                'label' => 'Cold Bright Silver',
                'description' => 'Shining cold silver foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/bright-silver.png',
            ],
            [
                'code' => 'cold_green_gold',
                'label' => 'Cold Green Gold',
                'description' => 'Rich cold green gold foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/green-gold.png',
            ],
            [
                'code' => 'cold_matte_gold',
                'label' => 'Cold Matte Gold',
                'description' => 'Sophisticated matte gold foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/matte-gold.png',
            ],
            [
                'code' => 'cold_matte_silver',
                'label' => 'Cold Matte Silver',
                'description' => 'Elegant cold silver foil.',
                'swatch_image' => '/images/product-options/business-cards/swatches/cold/matte-silver.png',
            ],
        ] as $value) {
            if (! in_array($value['code'], $existingCodes, true)) {
                $specialValues[] = $value;
            }
        }

        $specialFinish['values'] = array_values($specialValues);
        $options['special_finish'] = $specialFinish;

        return $options;
    }

    private function replaceBusinessCardCopy(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->replaceBusinessCardCopy($item),
                $value,
            );
        }

        if (! is_string($value)) {
            return $value;
        }

        return str_replace(
            [
                'Business Cards',
                'business cards',
                'Business Card',
                'business card',
            ],
            [
                'Postcards',
                'postcards',
                'Postcard',
                'postcard',
            ],
            $value,
        );
    }
}
