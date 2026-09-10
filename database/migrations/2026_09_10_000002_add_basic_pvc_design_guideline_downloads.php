<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, array<string, string>>
     */
    private const DESIGN_GUIDELINE_DOWNLOADS = [
        [
            'id' => 'pdf',
            'label' => 'PDF',
            'extension' => '.pdf',
            'href' => '/templates/pdf.zip',
            'color' => '#dc2626',
        ],
        [
            'id' => 'illustrator',
            'label' => 'Illustrator',
            'extension' => '.ai',
            'href' => '/templates/ai.zip',
            'color' => '#f97316',
        ],
        [
            'id' => 'indesign',
            'label' => 'InDesign',
            'extension' => '.indd',
            'href' => '/templates/indd.zip',
            'color' => '#ec4899',
        ],
        [
            'id' => 'jpeg',
            'label' => 'Jpeg',
            'extension' => '.jpg',
            'href' => '/templates/jpg.zip',
            'color' => '#0f766e',
        ],
    ];

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', 'basic-pvc-card')
            ->first();

        if ($product === null) {
            return;
        }

        $config = $this->decodeConfig($product->product_config ?? null);
        $detailSections = is_array($config['detail_sections'] ?? null)
            ? $config['detail_sections']
            : [];
        $designSpecifications = is_array($detailSections['design_specifications'] ?? null)
            ? $detailSections['design_specifications']
            : [];
        $designSpecifications['downloads'] = self::DESIGN_GUIDELINE_DOWNLOADS;
        $detailSections['design_specifications'] = $designSpecifications;
        $config['detail_sections'] = $detailSections;

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'product_config' => json_encode(
                    $config,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ]);
    }

    public function down(): void
    {
        // The shared guideline downloads are intentionally not removed on rollback.
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
