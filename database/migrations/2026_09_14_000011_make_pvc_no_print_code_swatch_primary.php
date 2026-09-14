<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NO_PRINT_CODE_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    /**
     * @var array<string, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    private const NO_PRINT_CODE_GALLERY_RULES = [
        'basic-pvc-card' => [
            'id' => 'no_print_code_gallery',
            'match' => ['print_code' => 'no_print_code'],
            'images' => [self::NO_PRINT_CODE_IMAGE],
            'primary' => self::NO_PRINT_CODE_IMAGE,
        ],
        'standard-pvc-card' => [
            'id' => 'no_print_code_gallery',
            'match' => ['print_code_or_signature_stripe' => 'no_print_code_or_signature_stripe'],
            'images' => [self::NO_PRINT_CODE_IMAGE],
            'primary' => self::NO_PRINT_CODE_IMAGE,
        ],
        'premium-pvc-card' => [
            'id' => 'no_print_code_gallery',
            'match' => ['print_code' => 'no_print_code'],
            'images' => [self::NO_PRINT_CODE_IMAGE],
            'primary' => self::NO_PRINT_CODE_IMAGE,
        ],
    ];

    public function up(): void
    {
        foreach (self::NO_PRINT_CODE_GALLERY_RULES as $slug => $replacement) {
            $this->updateProduct($slug, $replacement);
        }
    }

    public function down(): void
    {
        // The no-print-code swatch is intentionally not reverted.
    }

    /**
     * @param  array{id: string, match: array<string, string>, images: array<int, string>, primary: string}  $replacement
     */
    private function updateProduct(string $slug, array $replacement): void
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
        $replacementMatch = $replacement['match'];
        $replacementId = $replacement['id'];

        $rules = array_values(array_filter(
            $rules,
            static function (mixed $rule) use ($replacementId, $replacementMatch): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return ($rule['id'] ?? null) !== $replacementId
                    && $match !== $replacementMatch;
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
            $replacement,
            ...$otherRules,
        ];
        $config['media'] = $media;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => $this->encodeConfig($config),
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
