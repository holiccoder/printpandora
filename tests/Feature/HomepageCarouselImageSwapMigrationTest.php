<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCarouselImageSwapMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_16_000002_update_home_carousel_order_and_image.php';

    private const NEW_THIRD_IMAGE = '/images/home/homepage-carousel-03-ryft-metal-cutout.png';

    public function test_homepage_images_are_moved_without_changing_slide_copy(): void
    {
        SiteSetting::query()->create([
            'key' => 'site',
            'value' => [
                'homepage' => [
                    'hero_carousel' => [
                        'slides' => [
                            [
                                'headline' => 'Keep first copy',
                                'subheadline' => 'Keep first description',
                                'cta_text' => 'Keep first CTA',
                                'image_url' => '/images/home/homepage-carousel-01.png',
                                'mobile_image_url' => '/images/home/mobile-banners/01-mobile.png',
                            ],
                            [
                                'headline' => 'Keep second copy',
                                'image_url' => '/images/home/homepage-carousel-02.webp',
                            ],
                            [
                                'headline' => 'Keep third copy',
                                'subheadline' => 'Keep third description',
                                'cta_text' => 'Keep third CTA',
                                'image_url' => '/images/home/homepage-carousel-03.webp',
                                'mobile_image_url' => '/images/home/mobile-banners/03-mobile.png',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();

        $slides = $this->slides();

        $this->assertSame('/images/home/homepage-carousel-03.webp', $slides[0]['image_url']);
        $this->assertSame(self::NEW_THIRD_IMAGE, $slides[2]['image_url']);
        $this->assertSame('Keep first copy', $slides[0]['headline']);
        $this->assertSame('Keep first description', $slides[0]['subheadline']);
        $this->assertSame('Keep first CTA', $slides[0]['cta_text']);
        $this->assertSame('Keep third copy', $slides[2]['headline']);
        $this->assertSame('Keep third description', $slides[2]['subheadline']);
        $this->assertSame('Keep third CTA', $slides[2]['cta_text']);
        $this->assertSame('/images/home/mobile-banners/01-mobile.png', $slides[0]['mobile_image_url']);
        $this->assertSame('/images/home/mobile-banners/03-mobile.png', $slides[2]['mobile_image_url']);

        $afterFirstRun = $slides;
        $migration->up();

        $this->assertSame($afterFirstRun, $this->slides());

        $migration->down();

        $slides = $this->slides();
        $this->assertSame('/images/home/homepage-carousel-01.png', $slides[0]['image_url']);
        $this->assertSame('/images/home/homepage-carousel-03.webp', $slides[2]['image_url']);
        $this->assertSame('Keep first copy', $slides[0]['headline']);
        $this->assertSame('Keep third copy', $slides[2]['headline']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function slides(): array
    {
        return SiteSetting::query()
            ->where('key', 'site')
            ->value('value')['homepage']['hero_carousel']['slides'];
    }
}
