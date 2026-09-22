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
        // Updated texture artwork is intentionally not reverted on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateCanonicalConfig(array $config): array
    {
        [$config, $textureChanged] = $this->updateCanonicalTexture($config);
        [$config, $galleryChanged] = $this->updateCanonicalGallery($config);

        return [$config, $textureChanged || $galleryChanged];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateCanonicalTexture(array $config): array
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $group = $options['texture'] ?? null;

        if (! is_array($group) || ! is_array($group['values'] ?? null)) {
            return [$config, false];
        }

        $definitions = [];

        foreach (ClassicSpecialBusinessCardTexture::mappedTextureDefinitions() as $definition) {
            $definitions[$definition['code']] = $definition;
        }

        $values = [];
        $seen = [];
        $changed = false;

        foreach ($group['values'] as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if (! isset($definitions[$code])) {
                $values[] = $value;

                continue;
            }

            if (isset($seen[$code])) {
                $changed = true;

                continue;
            }

            $normalized = array_replace($value, $definitions[$code]);
            $values[] = $normalized;
            $seen[$code] = true;
            $changed = $changed || $normalized !== $value;
        }

        foreach ($definitions as $code => $definition) {
            if (isset($seen[$code])) {
                continue;
            }

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
    private function updateCanonicalGallery(array $config): array
    {
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $existingRules = is_array($media['gallery_rules'] ?? null)
            ? array_values($media['gallery_rules'])
            : [];
        $mappedRules = ClassicSpecialBusinessCardTexture::mappedGalleryRules();
        $mappedIds = array_map(
            static fn (array $rule): string => $rule['id'],
            $mappedRules,
        );
        $mappedCodes = array_map(
            static fn (array $definition): string => $definition['code'],
            ClassicSpecialBusinessCardTexture::mappedTextureDefinitions(),
        );
        $filteredRules = array_values(array_filter(
            $existingRules,
            static function (mixed $rule) use ($mappedCodes, $mappedIds): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

                return ! in_array((string) ($rule['id'] ?? ''), $mappedIds, true)
                    && ! in_array((string) ($match['texture'] ?? ''), $mappedCodes, true);
            },
        ));
        $defaultRules = [];
        $otherRules = [];

        foreach ($filteredRules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (($rule['id'] ?? null) === 'default' || $match === []) {
                if ($defaultRules === []) {
                    $defaultRules[] = $rule;
                }

                continue;
            }

            $otherRules[] = $rule;
        }

        $newRules = [
            ...$defaultRules,
            ...$mappedRules,
            ...$otherRules,
        ];

        if ($newRules === $existingRules) {
            return [$config, false];
        }

        $media['gallery_rules'] = $newRules;
        $config['media'] = $media;

        return [$config, true];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateLegacyOptions(array $legacy): array
    {
        [$legacy, $textureChanged] = $this->updateLegacyTexture($legacy);
        [$legacy, $galleryChanged] = $this->updateLegacyGallery($legacy);

        return [$legacy, $textureChanged || $galleryChanged];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function updateLegacyTexture(array $legacy): array
    {
        $texture = $legacy['texture'] ?? null;

        if (! is_array($texture)) {
            return [$legacy, false];
        }

        $definitions = [];

        foreach (ClassicSpecialBusinessCardTexture::mappedTextureDefinitions() as $definition) {
            $definitions[$definition['code']] = [
                'name' => $definition['label'],
                'code' => $definition['code'],
                'swatch_image' => $definition['swatch_image'],
            ];
        }

        $values = [];
        $seen = [];
        $changed = false;

        foreach ($texture as $value) {
            if (! is_array($value)) {
                $values[] = $value;

                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if (! isset($definitions[$code])) {
                $values[] = $value;

                continue;
            }

            if (isset($seen[$code])) {
                $changed = true;

                continue;
            }

            $normalized = array_replace($value, $definitions[$code]);
            $values[] = $normalized;
            $seen[$code] = true;
            $changed = $changed || $normalized !== $value;
        }

        foreach ($definitions as $code => $definition) {
            if (isset($seen[$code])) {
                continue;
            }

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
    private function updateLegacyGallery(array $legacy): array
    {
        $galleries = is_array($legacy['galleries'] ?? null)
            ? array_values($legacy['galleries'])
            : [];

        if ($galleries === []) {
            return [$legacy, false];
        }

        $mappedRules = ClassicSpecialBusinessCardTexture::mappedGalleryRules();
        $mappedIds = array_map(
            static fn (array $rule): string => $rule['id'],
            $mappedRules,
        );
        $mappedCodes = array_map(
            static fn (array $definition): string => $definition['code'],
            ClassicSpecialBusinessCardTexture::mappedTextureDefinitions(),
        );
        $filteredGalleries = array_values(array_filter(
            $galleries,
            static function (mixed $gallery) use ($mappedCodes, $mappedIds): bool {
                if (! is_array($gallery)) {
                    return false;
                }

                $match = is_array($gallery['match'] ?? null) ? $gallery['match'] : [];

                return ! in_array((string) ($gallery['id'] ?? ''), $mappedIds, true)
                    && ! in_array((string) ($match['texture'] ?? ''), $mappedCodes, true);
            },
        ));
        $defaultGalleries = [];
        $otherGalleries = [];

        foreach ($filteredGalleries as $gallery) {
            if (! is_array($gallery)) {
                continue;
            }

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
                $mappedRules,
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
