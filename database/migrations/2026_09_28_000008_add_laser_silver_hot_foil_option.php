<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LASER_SILVER_PRIMARY_IMAGE = '/images/products/classic-solid/user-hot-laser-silver.png';

    /**
     * PVC and metal cards keep their product-specific gallery behavior.
     *
     * @var list<string>
     */
    private const GALLERY_EXCLUSIONS = [
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
        'classic-metal-business-cards',
        'premium-metal-business-cards',
        'luxe-metal-business-cards',
    ];

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'slug', 'product_config', 'product_options'])
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
                    $normalizedRules = in_array((string) $product->slug, self::GALLERY_EXCLUSIONS, true)
                        ? $rules
                        : $this->addLaserSilverGalleryRules($rules, $normalizedOptions);

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

                    if ($normalizedLegacy !== $legacy) {
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
        // The supplied option and artwork are intentionally retained by rollback.
    }

    /**
     * @param  array<int, mixed>  $rules
     * @param  array<string, mixed>  $options
     * @return array<int, mixed>
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
                if (is_array($value) && ($value['code'] ?? null) === 'laser_silver') {
                    $availableGroups[] = $groupKey;

                    break;
                }
            }
        }

        if ($availableGroups === []) {
            return $rules;
        }

        $remainingRules = array_values(array_filter(
            $rules,
            function (mixed $rule) use ($availableGroups): bool {
                if (! is_array($rule)) {
                    return false;
                }

                $match = $rule['match'] ?? [];

                if (! is_array($match)) {
                    return true;
                }

                foreach ($availableGroups as $groupKey) {
                    if (($match[$groupKey] ?? null) === 'laser_silver') {
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
                'match' => [$groupKey => 'laser_silver'],
                'images' => [self::LASER_SILVER_PRIMARY_IMAGE],
                'primary' => self::LASER_SILVER_PRIMARY_IMAGE,
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
