<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-quality-business-cards';

    private const DEFAULT_TEXTURE_CODE = 'shattered_glass_film';

    /**
     * @var array<string, string>
     */
    private const CANONICAL_DEFAULT_TEXTURE = [
        'code' => 'shattered_glass_film',
        'label' => 'Shattered Glass Film',
        'description' => '',
        'swatch_image' => '/images/product-options/business-cards/swatches/quality/shattered-glass-film.png',
    ];

    /**
     * @var array<string, string>
     */
    private const CANONICAL_MATTE_OPTION = [
        'code' => 'matte',
        'label' => 'Matte',
        'description' => 'With a smooth feel. Shine-free so no glare.',
        'swatch_image' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
    ];

    /**
     * @var array<string, string>
     */
    private const CANONICAL_GLOSS_OPTION = [
        'code' => 'gloss',
        'label' => 'Gloss',
        'description' => 'Eye-catchingly shiny. Makes color photos pop.',
        'swatch_image' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_MATTE_OPTION = [
        'name' => 'Matte',
        'code' => 'matte',
        'description' => 'With a smooth feel. Shine-free so no glare.',
        'swatch_image' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_GLOSS_OPTION = [
        'name' => 'Gloss',
        'code' => 'gloss',
        'description' => 'Eye-catchingly shiny. Makes color photos pop.',
        'swatch_image' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
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
        [$config, $configChanged] = $this->migrateCanonicalConfig($config);

        if ($configChanged) {
            $updates['product_config'] = $this->encode($config);
        }

        $legacy = $this->decode($product->product_options ?? null);
        [$legacy, $legacyChanged] = $this->migrateLegacyOptions($legacy);

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
        // The option move is intentionally not reversed on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function migrateCanonicalConfig(array $config): array
    {
        $original = $config;
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $hasPaperFinish = array_key_exists('paper_finish', $options);
        $hasTexture = array_key_exists('texture', $options);

        if ($hasPaperFinish || $hasTexture) {
            $texture = is_array($options['texture'] ?? null) ? $options['texture'] : [];
            $hasTextureValues = is_array($texture['values'] ?? null);
            $values = $hasTextureValues ? $texture['values'] : [];

            if ($values === []) {
                $values[] = self::CANONICAL_DEFAULT_TEXTURE;
            }

            $texture['values'] = $this->migrateTextureValues($values, false);
            $hasDefaultValue = false;

            foreach ($texture['values'] as $value) {
                if (is_array($value) && ($value['code'] ?? null) === self::DEFAULT_TEXTURE_CODE) {
                    $hasDefaultValue = true;

                    break;
                }
            }

            if (! $hasDefaultValue) {
                array_unshift($texture['values'], self::CANONICAL_DEFAULT_TEXTURE);
            }

            $default = strtolower(trim((string) ($texture['default'] ?? '')));
            if ($default === '' || in_array($default, ['uv', '3d uv', '3d_uv'], true)) {
                $texture['default'] = self::DEFAULT_TEXTURE_CODE;
            }

            if (! array_key_exists('label', $texture)) {
                $texture['label'] = 'Texture';
            }

            if (! array_key_exists('type', $texture)) {
                $texture['type'] = 'select';
            }

            if (! array_key_exists('required', $texture)) {
                $texture['required'] = true;
            }

            $options['texture'] = $texture;
            unset($options['paper_finish']);
            $config['options'] = $options;
        }

        $config = $this->migrateGalleryRules($config, 'media', 'gallery_rules');

        return [$config, $config !== $original];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function migrateLegacyOptions(array $legacy): array
    {
        $original = $legacy;
        $hasPaperFinish = array_key_exists('paper_finish', $legacy);
        $hasTexture = array_key_exists('texture', $legacy);

        if ($hasPaperFinish || $hasTexture) {
            $texture = is_array($legacy['texture'] ?? null) ? $legacy['texture'] : [];
            $legacy['texture'] = $this->migrateTextureValues($texture, true);
            unset($legacy['paper_finish']);
        }

        $legacy = $this->migrateGalleryRules($legacy, null, 'galleries');

        return [$legacy, $legacy !== $original];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    private function migrateTextureValues(array $values, bool $legacy): array
    {
        $migrated = [];
        $seen = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                $migrated[] = $value;

                continue;
            }

            $code = strtolower(trim((string) ($value['code'] ?? '')));
            $label = strtolower(trim((string) ($value['label'] ?? $value['name'] ?? '')));

            if ($code === 'uv' || in_array($label, ['uv', '3d uv', '3d_uv'], true)) {
                continue;
            }

            if (in_array($code, ['matte', 'gloss'], true)) {
                if (isset($seen[$code])) {
                    continue;
                }

                $defaults = $legacy
                    ? ($code === 'matte' ? self::LEGACY_MATTE_OPTION : self::LEGACY_GLOSS_OPTION)
                    : ($code === 'matte' ? self::CANONICAL_MATTE_OPTION : self::CANONICAL_GLOSS_OPTION);
                $migrated[] = array_replace($value, $defaults);
                $seen[$code] = true;

                continue;
            }

            $migrated[] = $value;

            if ($code !== '') {
                $seen[$code] = true;
            }
        }

        foreach (['matte', 'gloss'] as $code) {
            if (isset($seen[$code])) {
                continue;
            }

            $migrated[] = $legacy
                ? ($code === 'matte' ? self::LEGACY_MATTE_OPTION : self::LEGACY_GLOSS_OPTION)
                : ($code === 'matte' ? self::CANONICAL_MATTE_OPTION : self::CANONICAL_GLOSS_OPTION);
            $seen[$code] = true;
        }

        return array_values($migrated);
    }

    /**
     * Rename Classic Quality gallery conditions without changing images or
     * any other matching condition.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function migrateGalleryRules(array $payload, ?string $containerKey, string $rulesKey): array
    {
        $container = $containerKey === null
            ? $payload
            : (is_array($payload[$containerKey] ?? null) ? $payload[$containerKey] : []);
        $rules = $container[$rulesKey] ?? null;

        if (! is_array($rules)) {
            return $payload;
        }

        foreach ($rules as &$rule) {
            if (! is_array($rule) || ! is_array($rule['match'] ?? null)) {
                continue;
            }

            $match = $rule['match'];

            if (! array_key_exists('paper_finish', $match)) {
                continue;
            }

            $renamedMatch = [];

            foreach ($match as $key => $value) {
                if ($key === 'paper_finish') {
                    if (! array_key_exists('texture', $match)) {
                        $textureCode = $this->textureCode($value);

                        if ($textureCode !== null) {
                            $renamedMatch['texture'] = $textureCode;
                        }
                    }

                    continue;
                }

                $renamedMatch[$key] = $value;
            }

            $rule['match'] = $renamedMatch;
        }
        unset($rule);

        $container[$rulesKey] = array_values($rules);

        if ($containerKey === null) {
            return $container;
        }

        $payload[$containerKey] = $container;

        return $payload;
    }

    private function textureCode(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'matte' => 'matte',
            'gloss' => 'gloss',
            'uv', '3d uv', '3d_uv' => null,
            default => $normalized === '' ? null : str_replace(['-', ' '], '_', $normalized),
        };
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
