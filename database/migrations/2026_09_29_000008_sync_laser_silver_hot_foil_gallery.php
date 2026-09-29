<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRIMARY_IMAGE = '/images/products/classic-solid/user-hot-laser-silver.png';

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];
                $config = $this->decode($product->product_config ?? null);

                if ($config !== []) {
                    $options = is_array($config['options'] ?? null)
                        ? $config['options']
                        : [];
                    $normalizedOptions = BusinessCardOptionCatalog::normalizeHotFoilOptions($options);
                    $configChanged = $normalizedOptions !== $options;
                    $config['options'] = $normalizedOptions;

                    $media = is_array($config['media'] ?? null) ? $config['media'] : [];
                    $rules = is_array($media['gallery_rules'] ?? null)
                        ? $media['gallery_rules']
                        : [];
                    $normalizedRules = $this->addLaserSilverGalleryRules(
                        $rules,
                        $normalizedOptions,
                    );

                    if ($normalizedRules !== $rules) {
                        $media['gallery_rules'] = $normalizedRules;
                        $config['media'] = $media;
                        $configChanged = true;
                    }

                    if ($configChanged) {
                        $updates['product_config'] = $this->encode($config);
                    }
                }

                $legacy = $this->decode($product->product_options ?? null);

                if ($legacy !== []) {
                    $normalizedLegacy = BusinessCardOptionCatalog::normalizeHotFoilOptions($legacy);
                    $legacyChanged = $normalizedLegacy !== $legacy;

                    if (is_array($normalizedLegacy['galleries'] ?? null)) {
                        $galleries = $this->addLaserSilverGalleryRules(
                            $normalizedLegacy['galleries'],
                            $normalizedLegacy,
                        );

                        if ($galleries !== $normalizedLegacy['galleries']) {
                            $normalizedLegacy['galleries'] = $galleries;
                            $legacyChanged = true;
                        }
                    }

                    if (is_array(data_get($normalizedLegacy, 'media.gallery_rules'))) {
                        $mediaRules = $this->addLaserSilverGalleryRules(
                            data_get($normalizedLegacy, 'media.gallery_rules'),
                            $normalizedLegacy,
                        );

                        if ($mediaRules !== data_get($normalizedLegacy, 'media.gallery_rules')) {
                            $normalizedLegacy['media']['gallery_rules'] = $mediaRules;
                            $legacyChanged = true;
                        }
                    }

                    if ($legacyChanged) {
                        $updates['product_options'] = $this->encode($normalizedLegacy);
                    }
                }

                if ($updates !== []) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        // The shared option and primary artwork are intentionally retained.
    }

    /**
     * @param  array<int, mixed>  $rules
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function addLaserSilverGalleryRules(array $rules, array $options): array
    {
        $availableGroups = [];

        foreach (['special_finish', 'hot_foil'] as $groupKey) {
            $group = $options[$groupKey] ?? null;
            $values = is_array($group) && array_key_exists('values', $group)
                ? $group['values']
                : $group;

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (
                    (is_array($value) && ($value['code'] ?? null) === BusinessCardOptionCatalog::LASER_SILVER_HOT_FOIL_CODE)
                    || (
                        is_scalar($value)
                        && str_replace(['-', ' '], '_', strtolower(trim((string) $value)))
                            === BusinessCardOptionCatalog::LASER_SILVER_HOT_FOIL_CODE
                    )
                ) {
                    $availableGroups[] = $groupKey;

                    break;
                }
            }
        }

        $availableGroups = array_values(array_unique($availableGroups));

        if ($availableGroups === []) {
            return array_values(array_filter($rules, is_array(...)));
        }

        $remainingRules = array_values(array_filter(
            $rules,
            function (mixed $rule) use ($availableGroups): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $id = (string) ($rule['id'] ?? '');
                if (in_array($id, [
                    'shared-foil-laser_silver',
                    'shared-foil-hot_foil-laser_silver',
                ], true)) {
                    return false;
                }

                $match = $rule['match'] ?? [];

                if (! is_array($match)) {
                    return true;
                }

                foreach ($availableGroups as $groupKey) {
                    if (
                        str_replace(['-', ' '], '_', strtolower(trim((string) ($match[$groupKey] ?? ''))))
                        === BusinessCardOptionCatalog::LASER_SILVER_HOT_FOIL_CODE
                    ) {
                        return false;
                    }
                }

                return true;
            },
        ));

        foreach ($availableGroups as $groupKey) {
            $remainingRules[] = [
                'id' => $groupKey === 'special_finish'
                    ? 'shared-foil-laser_silver'
                    : 'shared-foil-'.$groupKey.'-laser_silver',
                'match' => [$groupKey => BusinessCardOptionCatalog::LASER_SILVER_HOT_FOIL_CODE],
                'images' => [self::PRIMARY_IMAGE],
                'primary' => self::PRIMARY_IMAGE,
            ];
        }

        return $remainingRules;
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
