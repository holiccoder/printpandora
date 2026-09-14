<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config'])
            ->whereNotNull('product_config')
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $config = is_string($product->product_config)
                    ? json_decode($product->product_config, true)
                    : $product->product_config;

                if (! is_array($config)) {
                    return;
                }

                $group = $config['options']['special_finish'] ?? null;

                if (! is_array($group) || ! is_array($group['values'] ?? null)) {
                    return;
                }

                $hasFoilValue = collect($group['values'])->contains(
                    static function (mixed $value): bool {
                        if (! is_array($value)) {
                            return false;
                        }

                        $text = strtolower(implode(' ', array_filter([
                            (string) ($value['code'] ?? ''),
                            (string) ($value['label'] ?? ''),
                            (string) ($value['description'] ?? ''),
                        ])));

                        return str_contains($text, 'foil') || str_contains($text, '烫');
                    },
                );

                if (! $hasFoilValue || ($group['type'] ?? null) === 'multi_select') {
                    return;
                }

                $config['options']['special_finish']['type'] = 'multi_select';

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['product_config' => json_encode(
                        $config,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    )]);
            });
    }

    public function down(): void
    {
        // The new selection contract is intentionally not reverted.
    }
};
