<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-special-business-cards';

    private const OBSOLETE_TEXTURE_CODE = 'pin_hole_paper';

    /**
     * @var array<string, string>
     */
    private const CANONICAL_MATTE_OPTION = [
        'code' => 'matte',
        'label' => 'Matte',
        'description' => '',
        'swatch_image' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_MATTE_OPTION = [
        'name' => 'Matte',
        'code' => 'matte',
        'description' => '',
        'swatch_image' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
    ];

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $updates = [];

        $config = $this->decode($product->product_config ?? null);
        [$config, $configChanged] = $this->replaceCanonicalTexture($config);

        if ($configChanged) {
            $updates['product_config'] = $this->encode($config);
        }

        $legacy = $this->decode($product->product_options ?? null);
        [$legacy, $legacyChanged] = $this->replaceLegacyTexture($legacy);

        if ($legacyChanged) {
            $updates['product_options'] = $this->encode($legacy);
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Removed product options are intentionally not restored on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function replaceCanonicalTexture(array $config): array
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $group = $options['texture'] ?? null;

        if (! is_array($group) || ! is_array($group['values'] ?? null)) {
            return [$config, false];
        }

        $values = [];
        $changed = false;
        $hasMatte = false;

        foreach ($group['values'] as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if ($code === self::OBSOLETE_TEXTURE_CODE) {
                $changed = true;

                if (! $hasMatte) {
                    $values[] = array_replace($value, self::CANONICAL_MATTE_OPTION);
                    $hasMatte = true;
                }

                continue;
            }

            if ($code === 'matte') {
                $normalized = array_replace($value, self::CANONICAL_MATTE_OPTION);
                $changed = $changed || $normalized !== $value || $hasMatte;

                if (! $hasMatte) {
                    $values[] = $normalized;
                    $hasMatte = true;
                }

                continue;
            }

            $values[] = $value;
        }

        if (! $changed) {
            return [$config, false];
        }

        if (! $hasMatte) {
            $values[] = self::CANONICAL_MATTE_OPTION;
        }

        $group['default'] = 'matte';
        $group['values'] = array_values($values);
        $options['texture'] = $group;
        $config['options'] = $options;

        return [$config, true];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function replaceLegacyTexture(array $legacy): array
    {
        $texture = $legacy['texture'] ?? null;

        if (! is_array($texture)) {
            return [$legacy, false];
        }

        $values = [];
        $changed = false;
        $hasMatte = false;

        foreach ($texture as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if ($code === self::OBSOLETE_TEXTURE_CODE) {
                $changed = true;

                if (! $hasMatte) {
                    $values[] = array_replace($value, self::LEGACY_MATTE_OPTION);
                    $hasMatte = true;
                }

                continue;
            }

            if ($code === 'matte') {
                $normalized = array_replace($value, self::LEGACY_MATTE_OPTION);
                $changed = $changed || $normalized !== $value || $hasMatte;

                if (! $hasMatte) {
                    $values[] = $normalized;
                    $hasMatte = true;
                }

                continue;
            }

            $values[] = $value;
        }

        if (! $changed) {
            return [$legacy, false];
        }

        if (! $hasMatte) {
            $values[] = self::LEGACY_MATTE_OPTION;
        }

        $legacy['texture'] = array_values($values);

        return [$legacy, true];
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
