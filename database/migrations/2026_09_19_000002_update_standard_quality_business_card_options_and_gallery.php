<?php

use App\Support\BusinessCardOptionCatalog;
use App\Support\StandardQualityBusinessCardGallery;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = StandardQualityBusinessCardGallery::PRODUCT_SLUG;

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

        if ($config !== []) {
            $config = $this->updateCanonicalConfig($config);
            $encodedConfig = $this->encode($config);

            if ($encodedConfig !== ($product->product_config ?? null)) {
                $updates['product_config'] = $encodedConfig;
            }
        }

        if (($product->featured_image ?? null) !== StandardQualityBusinessCardGallery::DEFAULT_GALLERY[0]) {
            $updates['featured_image'] = StandardQualityBusinessCardGallery::DEFAULT_GALLERY[0];
        }

        $legacy = $this->decode($product->product_options ?? null);

        if ($legacy !== []) {
            $legacy = $this->updateLegacyOptions($legacy);
            $encodedLegacy = $this->encode($legacy);

            if ($encodedLegacy !== ($product->product_options ?? null)) {
                $updates['product_options'] = $encodedLegacy;
            }
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Supplied artwork and option-contract replacement are intentionally
        // not reverted on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function updateCanonicalConfig(array $config): array
    {
        $config['options'] = BusinessCardOptionCatalog::normalize(
            self::PRODUCT_SLUG,
            is_array($config['options'] ?? null) ? $config['options'] : [],
        ) ?? [];

        return StandardQualityBusinessCardGallery::synchronizeConfig($config);
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function updateLegacyOptions(array $legacy): array
    {
        $legacyOptions = [];

        foreach (['sizes', 'corners', 'texture', 'uv_finish', 'special_finish'] as $groupKey) {
            if (is_array($legacy[$groupKey] ?? null)) {
                $legacyOptions[$groupKey] = [
                    'values' => array_values($legacy[$groupKey]),
                ];
            }
        }

        $normalizedOptions = BusinessCardOptionCatalog::normalize(
            self::PRODUCT_SLUG,
            $legacyOptions,
        ) ?? [];

        foreach ($normalizedOptions as $groupKey => $group) {
            if (! is_array($group) || ! is_array($group['values'] ?? null)) {
                continue;
            }

            $legacy[$groupKey] = array_values(array_map(
                static function (mixed $value): mixed {
                    if (! is_array($value)) {
                        return $value;
                    }

                    if (array_key_exists('label', $value) && ! array_key_exists('name', $value)) {
                        $value['name'] = $value['label'];
                    }

                    return $value;
                },
                $group['values'],
            ));
        }

        unset($legacy['paper_finish']);

        $existingRules = [];

        foreach (is_array($legacy['galleries'] ?? null) ? $legacy['galleries'] : [] as $gallery) {
            if (! is_array($gallery)) {
                continue;
            }

            $existingRules[] = [
                'id' => (string) ($gallery['id'] ?? ''),
                'is_default' => (bool) ($gallery['is_default'] ?? false),
                'match' => is_array($gallery['match'] ?? null) ? $gallery['match'] : [],
                'images' => is_array($gallery['images'] ?? null) ? array_values($gallery['images']) : [],
                'primary' => (string) ($gallery['primary'] ?? ($gallery['images'][0] ?? '')),
            ];
        }

        $synced = StandardQualityBusinessCardGallery::synchronizeConfig([
            'options' => $normalizedOptions,
            'media' => ['gallery_rules' => $existingRules],
        ]);

        $legacy['galleries'] = array_values(array_map(
            static function (array $rule): array {
                $gallery = [
                    'id' => $rule['id'] ?? '',
                    'match' => is_array($rule['match'] ?? null) ? $rule['match'] : [],
                    'images' => is_array($rule['images'] ?? null) ? array_values($rule['images']) : [],
                ];

                if (($rule['is_default'] ?? false) === true) {
                    $gallery['is_default'] = true;
                }

                return $gallery;
            },
            is_array($synced['media']['gallery_rules'] ?? null)
                ? $synced['media']['gallery_rules']
                : [],
        ));

        return $legacy;
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
