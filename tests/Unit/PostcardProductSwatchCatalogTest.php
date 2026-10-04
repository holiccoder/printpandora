<?php

namespace Tests\Unit;

use App\Support\PostcardProductSwatchCatalog;
use PHPUnit\Framework\TestCase;

class PostcardProductSwatchCatalogTest extends TestCase
{
    public function test_super_luxe_swatches_are_restored_without_changing_primary_images(): void
    {
        $primary = '/images/products/postcards/gallery/super-luxe-01.png';
        $config = [
            'options' => [
                'texture' => [
                    'values' => array_map(
                        static fn (int $number): array => [
                            'code' => "inkpavo_j{$number}",
                            'swatch_image' => '/images/products/postcards/gallery/super-luxe-0'.$number.'.png',
                        ],
                        range(1, 8),
                    ),
                ],
            ],
            'media' => [
                'gallery_rules' => [[
                    'id' => 'default',
                    'match' => [],
                    'images' => [$primary],
                    'primary' => $primary,
                ]],
            ],
        ];

        $result = PostcardProductSwatchCatalog::synchronizeConfig(
            $config,
            'super-luxe-postcards',
        );

        $this->assertSame(
            array_values(PostcardProductSwatchCatalog::swatchesFor('super-luxe-postcards')['texture']),
            array_column($result['options']['texture']['values'], 'swatch_image'),
        );
        $this->assertSame($config['media'], $result['media']);
    }

    public function test_super_luxe_paper_finish_alias_is_restored_without_changing_primary_images(): void
    {
        $primary = '/images/products/postcards/gallery/super-luxe-01.png';
        $config = [
            'options' => [
                'paper_finish' => [
                    'values' => [
                        ['code' => 'matte', 'swatch_image' => '/images/old-matte.png'],
                        ['code' => 'gloss', 'swatch_image' => '/images/old-gloss.png'],
                    ],
                ],
            ],
            'media' => [
                'gallery_rules' => [[
                    'id' => 'default',
                    'match' => [],
                    'images' => [$primary],
                    'primary' => $primary,
                ]],
            ],
        ];

        $result = PostcardProductSwatchCatalog::synchronizeConfig(
            $config,
            'super-luxe-postcards',
        );

        $this->assertSame(
            array_values(PostcardProductSwatchCatalog::swatchesFor('super-luxe-postcards')['paper_finish']),
            array_column($result['options']['paper_finish']['values'], 'swatch_image'),
        );
        $this->assertSame($config['media'], $result['media']);
    }

    public function test_quality_solid_paper_finish_swatches_are_restored_without_changing_primary_images(): void
    {
        $primary = '/images/products/postcards/finishes/quality-solid-matte.png';
        $config = [
            'options' => [
                'paper_finish' => [
                    'values' => [
                        ['code' => 'matte', 'swatch_image' => '/images/old-matte.png'],
                        ['code' => 'gloss', 'swatch_image' => '/images/old-gloss.png'],
                        ['code' => 'starry_film', 'swatch_image' => '/images/old-starlight.png'],
                        ['code' => 'holo_film', 'swatch_image' => '/images/old-holographic.png'],
                        ['code' => 'soft_touch_film', 'swatch_image' => '/images/old-soft-touch.png'],
                    ],
                ],
            ],
            'media' => [
                'gallery_rules' => [[
                    'id' => 'finish',
                    'match' => ['paper_finish' => 'matte'],
                    'images' => [$primary],
                    'primary' => $primary,
                ]],
            ],
        ];

        $result = PostcardProductSwatchCatalog::synchronizeConfig(
            $config,
            'quality-solid-postcards',
        );

        $this->assertSame(
            array_values(PostcardProductSwatchCatalog::swatchesFor('quality-solid-postcards')['paper_finish']),
            array_column($result['options']['paper_finish']['values'], 'swatch_image'),
        );
        $this->assertSame($config['media'], $result['media']);
    }
}
