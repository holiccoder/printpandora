<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PNG_SWATCH = '/images/product-options/business-cards/swatches/muted-purple-gold.png';

    private const WEBP_SWATCH = '/images/product-options/business-cards/swatches/muted-purple-gold.webp';

    public function up(): void
    {
        $this->replaceSwatchPath(self::PNG_SWATCH, self::WEBP_SWATCH);
    }

    public function down(): void
    {
        $this->replaceSwatchPath(self::WEBP_SWATCH, self::PNG_SWATCH);
    }

    private function replaceSwatchPath(string $from, string $to): void
    {
        DB::transaction(function () use ($from, $to): void {
            foreach (Product::query()->get() as $product) {
                $changed = [];

                foreach (['product_config', 'product_options'] as $column) {
                    $value = $product->{$column};

                    if (is_array($value) && $this->replaceOptionSwatches($value, $from, $to)) {
                        $changed[$column] = $value;
                    }
                }

                if ($changed !== []) {
                    $product->forceFill($changed)->saveQuietly();
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function replaceOptionSwatches(array &$node, string $from, string $to): bool
    {
        $changed = false;

        if (($node['code'] ?? null) === 'muted_purple_gold'
            && ($node['swatch_image'] ?? null) === $from) {
            $node['swatch_image'] = $to;
            $changed = true;
        }

        foreach ($node as &$value) {
            if (is_array($value) && $this->replaceOptionSwatches($value, $from, $to)) {
                $changed = true;
            }
        }
        unset($value);

        return $changed;
    }
};
