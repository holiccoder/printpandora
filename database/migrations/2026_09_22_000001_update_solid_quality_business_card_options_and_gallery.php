<?php

use App\Support\SolidQualityBusinessCardGallery;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = SolidQualityBusinessCardGallery::PRODUCT_SLUG;

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

        $defaultImage = SolidQualityBusinessCardGallery::DEFAULT_GALLERY[0];

        if (($product->featured_image ?? null) !== $defaultImage) {
            $updates['featured_image'] = $defaultImage;
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
        // Supplied artwork and the option-contract replacement are not
        // reverted on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function updateCanonicalConfig(array $config): array
    {
        return SolidQualityBusinessCardGallery::synchronizeConfig($config);
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function updateLegacyOptions(array $legacy): array
    {
        $legacy = SolidQualityBusinessCardGallery::synchronizeLegacyOptions($legacy);
        $existingRules = [];

        foreach (is_array($legacy['galleries'] ?? null) ? $legacy['galleries'] : [] as $gallery) {
            if (! is_array($gallery)) {
                continue;
            }

            $images = is_array($gallery['images'] ?? null)
                ? array_values($gallery['images'])
                : [];

            $existingRules[] = [
                'id' => (string) ($gallery['id'] ?? ''),
                'is_default' => (bool) ($gallery['is_default'] ?? false),
                'match' => is_array($gallery['match'] ?? null) ? $gallery['match'] : [],
                'images' => $images,
                'primary' => (string) ($gallery['primary'] ?? ($images[0] ?? '')),
            ];
        }

        $synced = SolidQualityBusinessCardGallery::synchronizeConfig([
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
