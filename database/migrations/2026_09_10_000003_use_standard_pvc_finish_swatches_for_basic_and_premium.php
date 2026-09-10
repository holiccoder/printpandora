<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{label: string, description: string, swatch_image: string}>
     */
    private const PVC_FINISHES = [
        'matte' => [
            'label' => 'Matte',
            'description' => 'Smooth, non-reflective matte finish.',
            'swatch_image' => '/images/products/pvc/standard-pvc-matte.png',
        ],
        'gloss' => [
            'label' => 'Gloss',
            'description' => 'Shiny and highly reflective gloss finish.',
            'swatch_image' => '/images/products/pvc/standard-pvc-gloss.png',
        ],
        'frosted' => [
            'label' => 'Frosted Glass',
            'description' => 'A translucent frosted-glass finish with a soft, elegant look.',
            'swatch_image' => '/images/products/pvc/standard-pvc-frosted.png',
        ],
    ];

    public function up(): void
    {
        foreach (['basic-pvc-card', 'premium-pvc-card'] as $slug) {
            $this->updateProduct($slug);
        }
    }

    public function down(): void
    {
        // The shared swatches are intentionally not reverted on rollback.
    }

    private function updateProduct(string $slug): void
    {
        $product = DB::table('products')
            ->where('slug', $slug)
            ->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $paperFinish = is_array($options['paper_finish'] ?? null)
            ? $options['paper_finish']
            : [];
        $existingValues = is_array($paperFinish['values'] ?? null)
            ? $paperFinish['values']
            : [];
        $existingByCode = [];

        foreach ($existingValues as $value) {
            if (is_array($value) && is_string($value['code'] ?? null)) {
                $existingByCode[$value['code']] = $value;
            }
        }

        $values = [];

        foreach (self::PVC_FINISHES as $code => $defaults) {
            $values[] = array_replace(
                ['code' => $code],
                $defaults,
                $existingByCode[$code] ?? [],
                ['swatch_image' => $defaults['swatch_image']],
            );
        }

        $options['paper_finish'] = array_replace(
            [
                'label' => 'Paper Finish',
                'type' => 'select',
                'required' => true,
                'default' => 'matte',
            ],
            $paperFinish,
            ['values' => $values],
        );
        $config['options'] = $options;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConfig(mixed $encoded): array
    {
        if (is_array($encoded)) {
            return $encoded;
        }

        if (! is_string($encoded) || trim($encoded) === '') {
            return [];
        }

        $config = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($config) ? $config : [];
    }
};
