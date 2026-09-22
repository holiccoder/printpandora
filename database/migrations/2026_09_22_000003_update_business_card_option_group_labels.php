<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{group: string, label: string}>
     */
    private const PRODUCT_GROUP_LABELS = [
        'standard-quality-business-cards' => [
            'group' => 'texture',
            'label' => 'Paper Finish',
        ],
        'solid-quality-business-cards' => [
            'group' => 'uv_finish',
            'label' => '3D UV',
        ],
    ];

    public function up(): void
    {
        foreach (self::PRODUCT_GROUP_LABELS as $slug => $definition) {
            $product = DB::table('products')
                ->where('slug', $slug)
                ->first();

            if ($product === null) {
                continue;
            }

            $config = $this->decode($product->product_config ?? null);
            $group = $definition['group'];

            if (! is_array($config['options'][$group] ?? null)) {
                continue;
            }

            if (($config['options'][$group]['label'] ?? null) === $definition['label']) {
                continue;
            }

            $config['options'][$group]['label'] = $definition['label'];

            DB::table('products')
                ->where('id', $product->id)
                ->update(['product_config' => $this->encode($config)]);
        }
    }

    public function down(): void
    {
        // The corrected option-group titles are not reverted on rollback.
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
