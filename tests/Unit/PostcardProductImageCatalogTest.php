<?php

namespace Tests\Unit;

use App\Support\CardsAndPostcardsProductImageCatalog;
use PHPUnit\Framework\TestCase;

class PostcardProductImageCatalogTest extends TestCase
{
    public function test_requested_default_galleries_and_finish_rules_are_stable(): void
    {
        $expectedFinishRules = [
            'classic-standard-postcards' => [
                'paper_finish' => [
                    'gloss' => '/images/products/postcards/finishes/classic-standard-gloss.png',
                    'matte' => '/images/products/postcards/finishes/classic-standard-matte.png',
                ],
                'rounded' => '/images/products/postcards/finishes/classic-standard-rounded.png',
            ],
            'quality-standard-postcards' => [
                'texture' => [
                    'gloss' => '/images/products/postcards/finishes/quality-standard-gloss.png',
                    'matte' => '/images/products/postcards/finishes/quality-standard-matte.png',
                ],
                'rounded' => '/images/products/postcards/finishes/quality-standard-rounded.png',
            ],
            'quality-solid-postcards' => [
                'paper_finish' => [
                    'gloss' => '/images/products/postcards/finishes/quality-solid-gloss.png',
                    'matte' => '/images/products/postcards/finishes/quality-solid-matte.png',
                ],
                'rounded' => '/images/products/postcards/finishes/quality-solid-rounded.png',
            ],
        ];

        foreach ($expectedFinishRules as $slug => $expected) {
            $gallery = CardsAndPostcardsProductImageCatalog::postcardGalleryFor($slug);
            $rules = CardsAndPostcardsProductImageCatalog::postcardOptionGalleryRulesFor($slug);

            $this->assertCount(4, $gallery);

            foreach ($expected as $group => $groupRules) {
                if ($group === 'rounded') {
                    $rule = $this->findRule($rules, ['corners' => 'rounded']);
                    $this->assertSame($groupRules, $rule['primary']);

                    continue;
                }

                foreach ($groupRules as $value => $image) {
                    $rule = $this->findRule($rules, [
                        $group => $value,
                        'corners' => 'square',
                    ]);
                    $this->assertSame($image, $rule['primary']);
                }
            }
        }
    }

    public function test_classic_special_texture_rules_change_primary_images_without_touching_swatches(): void
    {
        $swatches = [
            'white_cardstock' => '/images/products/classic-special-business-cards/texture/white-cardstock.png',
            'eggshell_paper' => '/images/products/classic-special-business-cards/texture/eggshell-paper.png',
            'linen_paper' => '/images/products/classic-special-business-cards/texture/linen-paper.png',
            'water_ripple_paper' => '/images/products/classic-special-business-cards/texture/water-ripple-paper.png',
        ];
        $config = [
            'options' => [
                'texture' => [
                    'values' => array_map(
                        static fn (string $code, string $swatch): array => [
                            'code' => $code,
                            'swatch_image' => $swatch,
                        ],
                        array_keys($swatches),
                        array_values($swatches),
                    ),
                ],
            ],
            'media' => [
                'gallery_rules' => [[
                    'id' => 'default',
                    'match' => [],
                    'images' => ['/images/old-default.png'],
                    'primary' => '/images/old-default.png',
                ]],
            ],
        ];

        $result = CardsAndPostcardsProductImageCatalog::synchronizePostcardConfig(
            $config,
            'classic-special-postcards',
        );

        $this->assertSame($swatches, array_column($result['options']['texture']['values'], 'swatch_image', 'code'));

        $expected = [
            'white_cardstock' => '/images/products/postcards/textures/classic-special-white-cardstock.png',
            'eggshell_paper' => '/images/products/postcards/textures/classic-special-eggshell-paper.png',
            'linen_paper' => '/images/products/postcards/textures/classic-special-linen-paper.png',
            'water_ripple_paper' => '/images/products/postcards/textures/classic-special-water-ripple-paper.png',
        ];

        foreach ($expected as $texture => $image) {
            $rule = $this->findRule($result['media']['gallery_rules'], ['texture' => $texture]);
            $this->assertSame($image, $rule['primary']);
        }
    }

    public function test_every_postcard_gets_the_shared_cold_and_hot_foil_primary_rules(): void
    {
        foreach (CardsAndPostcardsProductImageCatalog::postcardSlugs() as $slug) {
            $rules = CardsAndPostcardsProductImageCatalog::postcardOptionGalleryRulesFor($slug);
            $matches = array_map(
                static fn (array $rule): string => (string) array_key_first($rule['match']),
                $rules,
            );

            $this->assertContains('special_finish', $matches);
            $this->assertTrue(
                count(array_filter(
                    $rules,
                    static fn (array $rule): bool => str_starts_with($rule['primary'], '/images/products/postcards/foil/'),
                )) >= 18,
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     * @param  array<string, string>  $match
     * @return array<string, mixed>
     */
    private function findRule(array $rules, array $match): array
    {
        foreach ($rules as $rule) {
            if (($rule['match'] ?? null) === $match) {
                return $rule;
            }
        }

        self::fail('Gallery rule not found for '.json_encode($match));
    }
}
