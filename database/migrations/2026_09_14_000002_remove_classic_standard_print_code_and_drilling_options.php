<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-standard-business-cards';

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
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];

        if (array_key_exists('print_code', $options) || array_key_exists('drill', $options)) {
            unset($options['print_code'], $options['drill']);
            $config['options'] = $options;
            $updates['product_config'] = $this->encode($config);
        }

        $legacy = $this->decode($product->product_options ?? null);

        if (array_key_exists('print_code', $legacy) || array_key_exists('drill', $legacy)) {
            unset($legacy['print_code'], $legacy['drill']);
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
