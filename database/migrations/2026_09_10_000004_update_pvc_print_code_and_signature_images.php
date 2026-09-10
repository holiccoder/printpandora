<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{label: string, description: string, swatch_image: string}>
     */
    private const PRINT_CODE_VALUES = [
        'no_print_code' => [
            'label' => 'No print code',
            'description' => 'Do not add a print code.',
            'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
        ],
        'print_code' => [
            'label' => 'Print code',
            'description' => 'Add a print code to the card.',
            'swatch_image' => '/images/products/pvc/pvc-print-code.png',
        ],
    ];

    /**
     * @var array<string, array{label: string, description: string, swatch_image: string}>
     */
    private const PRINT_CODE_OR_SIGNATURE_STRIPE_VALUES = [
        'no_print_code_or_signature_stripe' => [
            'label' => 'No print code or signature stripe',
            'description' => 'Do not add a print code or signature stripe.',
            'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
        ],
        'print_code' => [
            'label' => 'Print code',
            'description' => 'Add a print code to the card.',
            'swatch_image' => '/images/products/pvc/pvc-print-code.png',
        ],
        'signature_stripe' => [
            'label' => 'Signature stripe',
            'description' => 'Add a writable signature stripe.',
            'swatch_image' => '/images/products/pvc/pvc-signature-stripe.png',
        ],
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private const GALLERY_RULES = [
        'basic-pvc-card' => [
            'print_code_gallery' => [
                'id' => 'print_code_gallery',
                'match' => ['print_code' => 'print_code'],
                'images' => ['/images/products/pvc/pvc-print-code.png'],
                'primary' => '/images/products/pvc/pvc-print-code.png',
            ],
        ],
        'standard-pvc-card' => [
            'print_code_gallery' => [
                'id' => 'print_code_gallery',
                'match' => ['print_code_or_signature_stripe' => 'print_code'],
                'images' => ['/images/products/pvc/pvc-print-code.png'],
                'primary' => '/images/products/pvc/pvc-print-code.png',
            ],
            'signature_stripe_gallery' => [
                'id' => 'signature_stripe_gallery',
                'match' => ['print_code_or_signature_stripe' => 'signature_stripe'],
                'images' => ['/images/products/pvc/pvc-signature-stripe.png'],
                'primary' => '/images/products/pvc/pvc-signature-stripe.png',
            ],
        ],
        'premium-pvc-card' => [
            'print_code_gallery' => [
                'id' => 'print_code_gallery',
                'match' => ['print_code' => 'print_code'],
                'images' => ['/images/products/pvc/pvc-print-code.png'],
                'primary' => '/images/products/pvc/pvc-print-code.png',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (array_keys(self::GALLERY_RULES) as $slug) {
            $this->updateProduct($slug);
        }
    }

    public function down(): void
    {
        // The supplied PVC option images are intentionally not reverted.
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
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $optionKey = $slug === 'standard-pvc-card'
            ? 'print_code_or_signature_stripe'
            : 'print_code';
        $optionDefaults = $slug === 'standard-pvc-card'
            ? self::PRINT_CODE_OR_SIGNATURE_STRIPE_VALUES
            : self::PRINT_CODE_VALUES;
        $optionGroup = is_array($options[$optionKey] ?? null)
            ? $options[$optionKey]
            : [];
        $existingValues = is_array($optionGroup['values'] ?? null)
            ? $optionGroup['values']
            : [];
        $existingByCode = [];

        foreach ($existingValues as $value) {
            if (is_array($value) && is_string($value['code'] ?? null)) {
                $existingByCode[$value['code']] = $value;
            }
        }

        $values = [];

        foreach ($optionDefaults as $code => $defaults) {
            $values[] = array_replace(
                ['code' => $code],
                $defaults,
                $existingByCode[$code] ?? [],
                ['swatch_image' => $defaults['swatch_image']],
            );
        }

        $options[$optionKey] = array_replace(
            [
                'label' => $slug === 'standard-pvc-card'
                    ? 'Print Code or Signature Stripe'
                    : 'Print Code',
                'type' => 'select',
                'required' => true,
                'default' => array_key_first($optionDefaults),
            ],
            $optionGroup,
            ['values' => $values],
        );
        $config['options'] = $options;

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $rules = is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [];
        $replacementRules = self::GALLERY_RULES[$slug];
        $rules = array_values(array_filter(
            $rules,
            static function (mixed $rule) use ($optionKey, $replacementRules): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return ! array_key_exists((string) ($rule['id'] ?? ''), $replacementRules)
                    && ! array_key_exists($optionKey, $match);
            },
        ));
        $defaultRules = array_values(array_filter(
            $rules,
            static function (array $rule): bool {
                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return $match === [] || ($rule['id'] ?? null) === 'default';
            },
        ));
        $otherRules = array_values(array_filter(
            $rules,
            static function (array $rule): bool {
                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return $match !== [] && ($rule['id'] ?? null) !== 'default';
            },
        ));
        $media['gallery_rules'] = [
            ...$defaultRules,
            ...array_values($replacementRules),
            ...$otherRules,
        ];
        $config['media'] = $media;

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
