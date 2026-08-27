<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCarouselMobileImagesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_homepage_slides_receive_ordered_mobile_images(): void
    {
        SiteSetting::query()->create([
            'key' => 'site',
            'value' => [
                'homepage' => [
                    'hero_carousel' => [
                        'slides' => [
                            ['image_url' => '/images/home/old-first.webp'],
                            ['image_url' => '/images/home/old-second.webp'],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_08_26_000007_update_home_carousel_images.php',
        );
        $migration->up();

        $slides = SiteSetting::query()->where('key', 'site')->value('value')['homepage']['hero_carousel']['slides'];

        $this->assertSame('/images/home/homepage-carousel-01.png', $slides[0]['image_url']);
        $this->assertSame('/images/home/mobile-banners/01-mobile.png', $slides[0]['mobile_image_url']);
        $this->assertSame('/images/home/mobile-banners/02-mobile.png', $slides[1]['mobile_image_url']);
    }
}
