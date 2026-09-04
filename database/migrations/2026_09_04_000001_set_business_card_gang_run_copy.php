<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const PRODUCT_FEATURE_CARD_FILES = [
        'classic-standard-business-cards' => 'content/product-options/business-cards/classic-standard-business-cards.json',
        'classic-special-business-cards' => 'content/product-options/business-cards/classic-special-business-cards.json',
    ];

    public function up(): void
    {
        foreach (self::PRODUCT_FEATURE_CARD_FILES as $slug => $relativePath) {
            $path = base_path($relativePath);
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException("Unable to read {$path}.");
            }

            $source = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $featureCards = is_array($source['detail_sections']['feature_cards'] ?? null)
                ? $source['detail_sections']['feature_cards']
                : [];
            $gangRunCard = is_array($featureCards[1] ?? null) ? $featureCards[1] : [];

            if ($gangRunCard === []) {
                continue;
            }

            Product::query()
                ->where('slug', $slug)
                ->eachById(function (Product $product) use ($gangRunCard): void {
                    $config = is_array($product->product_config) ? $product->product_config : [];

                    if ($config !== []) {
                        $config['detail_sections'] = $this->withGangRunCard(
                            is_array($config['detail_sections'] ?? null)
                                ? $config['detail_sections']
                                : [],
                            $gangRunCard,
                        );

                        $product->forceFill(['product_config' => $config])->saveQuietly();

                        return;
                    }

                    $legacy = is_array($product->product_options) ? $product->product_options : [];

                    if ($legacy === []) {
                        return;
                    }

                    $legacy['detail_sections'] = $this->withGangRunCard(
                        is_array($legacy['detail_sections'] ?? null)
                            ? $legacy['detail_sections']
                            : [],
                        $gangRunCard,
                    );

                    $product->forceFill(['product_options' => $legacy])->saveQuietly();
                });
        }
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $gangRunCard
     * @return array<string, mixed>
     */
    private function withGangRunCard(array $details, array $gangRunCard): array
    {
        $cards = is_array($details['feature_cards'] ?? null)
            ? array_values($details['feature_cards'])
            : [];
        $cards[0] = is_array($cards[0] ?? null) ? $cards[0] : [];
        $cards[1] = array_replace(
            is_array($cards[1] ?? null) ? $cards[1] : [],
            $gangRunCard,
        );
        $details['feature_cards'] = array_values($cards);

        return $details;
    }

    public function down(): void
    {
        // Product copy is intentionally not reverted so a rollback cannot
        // overwrite edits made in the admin panel after this migration ran.
    }
};
