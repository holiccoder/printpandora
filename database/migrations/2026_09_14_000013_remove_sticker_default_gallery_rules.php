<?php

use App\Support\StickerProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->stickerProducts() as $product) {
            $config = json_decode((string) $product->product_config, true);

            if (! is_array($config)) {
                continue;
            }

            $rules = data_get($config, 'media.gallery_rules');

            if (! is_array($rules)) {
                continue;
            }

            $filteredRules = array_values(array_filter(
                $rules,
                static fn (mixed $rule): bool => ! is_array($rule)
                    || ! is_array($rule['match'] ?? null)
                    || $rule['match'] !== [],
            ));

            if (count($filteredRules) === count($rules)) {
                continue;
            }

            data_set($config, 'media.gallery_rules', $filteredRules);
            $this->saveConfig($product->id, $config);
        }
    }

    public function down(): void
    {
        foreach ($this->stickerProducts() as $product) {
            $config = json_decode((string) $product->product_config, true);

            if (! is_array($config)) {
                continue;
            }

            $rules = data_get($config, 'media.gallery_rules');
            $defaultImages = data_get($config, 'media.gallery');

            if (! is_array($rules) || ! is_array($defaultImages) || $defaultImages === []) {
                continue;
            }

            $hasDefaultRule = collect($rules)->contains(
                static fn (mixed $rule): bool => is_array($rule)
                    && is_array($rule['match'] ?? null)
                    && $rule['match'] === [],
            );

            if ($hasDefaultRule) {
                continue;
            }

            array_unshift($rules, [
                'id' => 'default',
                'match' => [],
                'images' => array_values($defaultImages),
                'primary' => $defaultImages[0],
            ]);

            data_set($config, 'media.gallery_rules', $rules);
            $this->saveConfig($product->id, $config);
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function stickerProducts()
    {
        return DB::table('products')
            ->whereIn('slug', StickerProductCatalog::slugs())
            ->get(['id', 'product_config']);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function saveConfig(int $productId, array $config): void
    {
        DB::table('products')
            ->where('id', $productId)
            ->update([
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                'updated_at' => now(),
            ]);
    }
};
