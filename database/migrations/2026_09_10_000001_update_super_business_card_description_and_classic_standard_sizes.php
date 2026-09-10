<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SUPER_DEFAULT_IMAGE = '/images/products/super-business-cards/super-business-cards-default-01.png';

    /**
     * @var array<int, string>
     */
    private const SUPER_DEFAULT_GALLERY = [
        '/images/products/super-business-cards/super-business-cards-default-01.png',
        '/images/products/super-business-cards/super-business-cards-default-02.png',
        '/images/products/super-business-cards/super-business-cards-default-03.png',
        '/images/products/super-business-cards/super-business-cards-default-04.png',
    ];

    private const SUPER_DESCRIPTION = '<p>Made from approximately 130 lb uncoated cover stock, each sheet is about 16pt thick. With no coating on either side, this stock produces softer, more understated colors in print.</p><p><strong>Available finishing options:</strong></p><ul><li>Square or rounded corners</li><li>Hot foil stamping or custom die-cut shapes <em>(4–5 business days)</em></li></ul><p><em>*For hot foil stamping, two-sided finishing is charged at twice the one-sided price.</em></p>';

    private const CLASSIC_STANDARD_STANDARD_DESCRIPTION = '2.0 x 3.5 inches';

    private const CLASSIC_STANDARD_SQUARE_DESCRIPTION = '2.5 x 2.5 inches';

    private const CLASSIC_STANDARD_CUSTOM_DESCRIPTION = '2.1 - 3.5 inches';

    private const STANDARD_SIZE_SWATCH = '/images/product-options/business-cards/swatches/standard-size.webp';

    private const SQUARE_SIZE_SWATCH = '/images/product-options/business-cards/swatches/square-size.webp';

    private const CUSTOM_SIZE_SWATCH = '/images/product-options/business-cards/swatches/custom-size.webp';

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->updateSuperBusinessCards();
            $this->updateClassicStandardBusinessCards();
        });
    }

    public function down(): void
    {
        // Product copy and option metadata are intentionally not reverted so
        // a rollback cannot overwrite edits made after this migration ran.
    }

    private function updateSuperBusinessCards(): void
    {
        $product = DB::table('products')->where('slug', 'super-business-cards')->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $config['product'] = is_array($config['product'] ?? null) ? $config['product'] : [];
        $config['product']['description'] = self::SUPER_DESCRIPTION;
        $config['product']['featured_image'] = self::SUPER_DEFAULT_IMAGE;

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $gallery = is_array($media['gallery'] ?? null)
            ? array_values($media['gallery'])
            : [];

        if ($gallery === []) {
            $gallery = self::SUPER_DEFAULT_GALLERY;
        } else {
            $gallery[0] = self::SUPER_DEFAULT_IMAGE;
        }

        $media['gallery'] = $gallery;
        $galleryRules = is_array($media['gallery_rules'] ?? null)
            ? array_values($media['gallery_rules'])
            : [];
        $defaultRuleIndex = null;

        foreach ($galleryRules as $index => $rule) {
            if (
                is_array($rule)
                && (($rule['id'] ?? null) === 'default' || ($rule['match'] ?? null) === [])
            ) {
                $defaultRuleIndex = $index;
                break;
            }
        }

        $defaultRule = $defaultRuleIndex !== null && is_array($galleryRules[$defaultRuleIndex] ?? null)
            ? $galleryRules[$defaultRuleIndex]
            : [
                'id' => 'default',
                'match' => [],
            ];
        $defaultRuleImages = is_array($defaultRule['images'] ?? null)
            ? array_values($defaultRule['images'])
            : [];

        if ($defaultRuleImages === []) {
            $defaultRuleImages = $gallery;
        } else {
            $defaultRuleImages[0] = self::SUPER_DEFAULT_IMAGE;
        }

        $defaultRule['id'] = 'default';
        $defaultRule['match'] = [];
        $defaultRule['images'] = $defaultRuleImages;
        $defaultRule['primary'] = self::SUPER_DEFAULT_IMAGE;

        if ($defaultRuleIndex === null) {
            $galleryRules[] = $defaultRule;
        } else {
            $galleryRules[$defaultRuleIndex] = $defaultRule;
        }

        $media['gallery_rules'] = array_values($galleryRules);
        $config['media'] = $media;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'description' => self::SUPER_DESCRIPTION,
                'featured_image' => self::SUPER_DEFAULT_IMAGE,
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ]);
    }

    private function updateClassicStandardBusinessCards(): void
    {
        $product = DB::table('products')
            ->where('slug', 'classic-standard-business-cards')
            ->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $sizeGroup = is_array($options['sizes'] ?? null) ? $options['sizes'] : [];
        $existingValues = is_array($sizeGroup['values'] ?? null)
            ? $sizeGroup['values']
            : [];
        $existingByCode = [];

        foreach ($existingValues as $value) {
            if (is_array($value) && is_string($value['code'] ?? null)) {
                $existingByCode[$value['code']] = $value;
            }
        }

        $standard = array_replace(
            [
                'code' => 'standard',
                'label' => 'Standard',
                'width' => '2.0',
                'height' => '3.5',
            ],
            $existingByCode['standard'] ?? [],
            [
                'description' => self::CLASSIC_STANDARD_STANDARD_DESCRIPTION,
                'swatch_image' => self::STANDARD_SIZE_SWATCH,
            ],
        );
        $square = array_replace(
            [
                'code' => 'square',
                'label' => 'Square',
                'width' => '2.5',
                'height' => '2.5',
            ],
            $existingByCode['square'] ?? [],
            [
                'description' => self::CLASSIC_STANDARD_SQUARE_DESCRIPTION,
                'swatch_image' => self::SQUARE_SIZE_SWATCH,
            ],
        );
        $custom = array_replace(
            [
                'code' => 'custom',
                'label' => 'Custom',
            ],
            $existingByCode['custom'] ?? [],
            [
                'description' => self::CLASSIC_STANDARD_CUSTOM_DESCRIPTION,
                'swatch_image' => self::CUSTOM_SIZE_SWATCH,
            ],
        );

        $options['sizes'] = array_replace(
            [
                'label' => 'Size',
                'type' => 'select',
                'required' => true,
                'default' => 'standard',
            ],
            $sizeGroup,
            [
                'values' => [$standard, $square, $custom],
            ],
        );
        $config['options'] = $options;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ]);
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

        $config = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($config) ? $config : [];
    }
};
