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

    /**
     * @var array<int, string>
     */
    private const LEGACY_DOWNLOAD_HREFS = [
        '/templates/template.psd',
        '/templates/template.ai',
        '/templates/template.indd',
        '/templates/template.jpg',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereNotNull('product_config')
            ->orderBy('id')
            ->get(['id', 'product_config'])
            ->each(function (object $product): void {
                $config = $this->decodeConfig($product->product_config ?? null);
                $detailSections = is_array($config['detail_sections'] ?? null)
                    ? $config['detail_sections']
                    : [];
                $designSpecifications = is_array($detailSections['design_specifications'] ?? null)
                    ? $detailSections['design_specifications']
                    : [];
                $downloads = $designSpecifications['downloads'] ?? null;

                if (! $this->hasLegacyDownloads($downloads)) {
                    return;
                }

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
            });
    }

    public function down(): void
    {
        // Legacy links intentionally remain removed on rollback.
    }

    private function hasLegacyDownloads(mixed $downloads): bool
    {
        if (! is_array($downloads)) {
            return false;
        }

        foreach ($downloads as $download) {
            if (
                is_array($download)
                && in_array($download['href'] ?? null, self::LEGACY_DOWNLOAD_HREFS, true)
            ) {
                return true;
            }
        }

        return false;
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
