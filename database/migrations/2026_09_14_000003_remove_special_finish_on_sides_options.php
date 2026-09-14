<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OPTION_KEY = 'special_finish_on_sides';

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);
                $options = is_array($config['options'] ?? null) ? $config['options'] : [];

                if (array_key_exists(self::OPTION_KEY, $options)) {
                    unset($options[self::OPTION_KEY]);
                    $config['options'] = $options;
                    $updates['product_config'] = $this->encode($config);
                }

                $legacy = $this->decode($product->product_options ?? null);

                if (array_key_exists(self::OPTION_KEY, $legacy)) {
                    unset($legacy[self::OPTION_KEY]);
                    $updates['product_options'] = $this->encode($legacy);
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
        // Removed product options are intentionally not restored on rollback.
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
