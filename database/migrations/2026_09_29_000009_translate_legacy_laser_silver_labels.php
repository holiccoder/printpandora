<?php

use App\Support\BusinessCardOptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

                    if ($normalizedOptions !== $options) {
                        $config['options'] = $normalizedOptions;
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
        // The English label is intentionally retained when rolling back.
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
