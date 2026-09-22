<?php

use App\Support\ClassicSpecialBusinessCardTexture;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-special-business-cards';

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
        [$config, $configChanged] = $this->updateCanonicalConfig($config);

        if ($configChanged) {
            $updates['product_config'] = $this->encode($config);
        }

        $legacy = $this->decode($product->product_options ?? null);
        [$legacy, $legacyChanged] = $this->updateLegacyOptions($legacy);

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
        // Added product options are intentionally not removed on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateCanonicalConfig(array $config): array
    {
        [$config, $textureChanged] = $this->addCanonicalTexture($config);
        [$config, $galleryChanged] = $this->addCanonicalGalleryRules($config);

        return [$config, $textureChanged || $galleryChanged];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function addCanonicalTexture(array $config): array
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $group = $options['texture'] ?? null;

        if (! is_array($group) || ! is_array($group['values'] ?? null)) {
            return [$config, false];
        }

        $definition = [
            'code' => ClassicSpecialBusinessCardTexture::CODE,
            'label' => ClassicSpecialBusinessCardTexture::LABEL,
            'description' => '',
            'swatch_image' => ClassicSpecialBusinessCardTexture::SQUARE_IMAGE,
        ];
        $values = [];
        $hasTexture = false;
        $changed = false;

        foreach ($group['values'] as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            if (($value['code'] ?? null) !== ClassicSpecialBusinessCardTexture::CODE) {
                $values[] = $value;

                continue;
            }

            $normalized = array_replace($value, $definition);

            if (! $hasTexture) {
                $values[] = $normalized;
                $hasTexture = true;
                $changed = $normalized !== $value;
            } else {
                $changed = true;
            }
        }

        if (! $hasTexture) {
            $values[] = $definition;
            $changed = true;
        }

        if (! $changed) {
            return [$config, false];
        }

        $group['values'] = $values;
        $options['texture'] = $group;
        $config['options'] = $options;

        return [$config, true];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function addCanonicalGalleryRules(array $config): array
    {
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $existingRules = is_array($media['gallery_rules'] ?? null)
            ? array_values($media['gallery_rules'])
            : [];
        $textureRuleIds = array_map(
            static fn (array $rule): string => $rule['id'],
            ClassicSpecialBusinessCardTexture::galleryRules(),
        );
        $filteredRules = array_values(array_filter(
            $existingRules,
            static fn (mixed $rule): bool => is_array($rule)
                && ! in_array((string) ($rule['id'] ?? ''), $textureRuleIds, true),
        ));
        $defaultRules = [];
        $otherRules = [];

        foreach ($filteredRules as $rule) {
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (($rule['id'] ?? null) === 'default' || $match === []) {
                if ($defaultRules === []) {
                    $defaultRules[] = $rule;
                }

                continue;
            }

            $otherRules[] = $rule;
        }

        $rules = [
            ...$defaultRules,
            ...ClassicSpecialBusinessCardTexture::galleryRules(),
            ...$otherRules,
        ];

        if ($rules === $existingRules) {
            return [$config, false];
        }

        $media['gallery_rules'] = $rules;
        $config['media'] = $media;

        return [$config, true];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateLegacyOptions(array $legacy): array
    {
        [$legacy, $textureChanged] = $this->addLegacyTexture($legacy);
        [$legacy, $galleryChanged] = $this->addLegacyGalleryRules($legacy);

        return [$legacy, $textureChanged || $galleryChanged];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function addLegacyTexture(array $legacy): array
    {
        $texture = $legacy['texture'] ?? null;

        if (! is_array($texture)) {
            return [$legacy, false];
        }

        $definition = [
            'name' => ClassicSpecialBusinessCardTexture::LABEL,
            'code' => ClassicSpecialBusinessCardTexture::CODE,
            'description' => '',
            'swatch_image' => ClassicSpecialBusinessCardTexture::SQUARE_IMAGE,
        ];
        $values = [];
        $hasTexture = false;
        $changed = false;

        foreach ($texture as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            if (($value['code'] ?? null) !== ClassicSpecialBusinessCardTexture::CODE) {
                $values[] = $value;

                continue;
            }

            $normalized = array_replace($value, $definition);

            if (! $hasTexture) {
                $values[] = $normalized;
                $hasTexture = true;
                $changed = $normalized !== $value;
            } else {
                $changed = true;
            }
        }

        if (! $hasTexture) {
            $values[] = $definition;
            $changed = true;
        }

        if (! $changed) {
            return [$legacy, false];
        }

        $legacy['texture'] = $values;

        return [$legacy, true];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function addLegacyGalleryRules(array $legacy): array
    {
        $galleries = is_array($legacy['galleries'] ?? null)
            ? array_values($legacy['galleries'])
            : [];

        if ($galleries === []) {
            return [$legacy, false];
        }

        $textureRuleIds = array_map(
            static fn (array $rule): string => $rule['id'],
            ClassicSpecialBusinessCardTexture::galleryRules(),
        );
        $filteredGalleries = array_values(array_filter(
            $galleries,
            static fn (mixed $gallery): bool => is_array($gallery)
                && ! in_array((string) ($gallery['id'] ?? ''), $textureRuleIds, true),
        ));
        $defaultGalleries = [];
        $otherGalleries = [];

        foreach ($filteredGalleries as $gallery) {
            $match = is_array($gallery['match'] ?? null) ? $gallery['match'] : [];
            $isDefault = ($gallery['id'] ?? null) === 'default'
                || (bool) ($gallery['is_default'] ?? false)
                || $match === [];

            if ($isDefault) {
                if ($defaultGalleries === []) {
                    $defaultGalleries[] = $gallery;
                }

                continue;
            }

            $otherGalleries[] = $gallery;
        }

        $newGalleries = [
            ...$defaultGalleries,
            ...array_map(
                static fn (array $rule): array => [
                    'id' => $rule['id'],
                    'match' => $rule['match'],
                    'images' => $rule['images'],
                ],
                ClassicSpecialBusinessCardTexture::galleryRules(),
            ),
            ...$otherGalleries,
        ];

        if ($newGalleries === $galleries) {
            return [$legacy, false];
        }

        $legacy['galleries'] = $newGalleries;

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
