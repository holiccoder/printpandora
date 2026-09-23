<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->get()
            ->each(function (object $product): void {
                $config = $this->decode($product->product_config ?? null);

                if ($config === []) {
                    return;
                }

                $options = BusinessCardOptionCatalog::normalize(
                    (string) $product->slug,
                    is_array($config['options'] ?? null) ? $config['options'] : [],
                );

                if ($options !== null) {
                    $config['options'] = $options;
                }

                $media = is_array($config['media'] ?? null) ? $config['media'] : [];
                $gallery = is_array($media['gallery'] ?? null)
                    ? array_values($media['gallery'])
                    : [];
                $galleryRules = is_array($media['gallery_rules'] ?? null)
                    ? $media['gallery_rules']
                    : [];

                if ($gallery !== []) {
                    $galleryRules = $this->withDefaultGalleryRule($galleryRules, $gallery);
                }

                $media['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                    $galleryRules,
                );
                $config['media'] = $media;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'product_config' => $this->encode($config),
                    ]);
            });
    }

    public function down(): void
    {
        // The supplied cotton special-finish artwork is not reverted.
    }

    /**
     * @param  array<int, mixed>  $rules
     * @param  array<int, mixed>  $gallery
     * @return array<int, array<string, mixed>>
     */
    private function withDefaultGalleryRule(array $rules, array $gallery): array
    {
        $normalized = [];
        $hasDefault = false;

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (($rule['id'] ?? null) === 'default' || $match === []) {
                if ($hasDefault) {
                    continue;
                }

                $hasDefault = true;
                $rule['id'] = 'default';
                $rule['match'] = [];
                $rule['images'] = $gallery;
                $rule['primary'] = $gallery[0];
            }

            $normalized[] = $rule;
        }

        if (! $hasDefault) {
            array_unshift($normalized, [
                'id' => 'default',
                'match' => [],
                'images' => $gallery,
                'primary' => $gallery[0],
            ]);
        }

        return $normalized;
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
