<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\HardcodedContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCarouselContentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_original_business_cards_headline_is_renamed(): void
    {
        SiteSetting::query()->create([
            'key' => 'site',
            'value' => [
                'homepage' => [
                    'hero_carousel' => [
                        'slides' => [
                            [
                                'headline' => 'Original Business Cards, designed to be remembered',
                            ],
                            [
                                'headline' => 'Keep this headline',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_08_26_000003_update_classic_business_card_carousel_headline.php',
        );
        $migration->up();

        $slides = SiteSetting::query()->where('key', 'site')->value('value')['homepage']['hero_carousel']['slides'];

        $this->assertSame(
            'Classic Business Cards, designed to be remembered',
            $slides[0]['headline'],
        );
        $this->assertSame('Keep this headline', $slides[1]['headline']);
    }

    public function test_renamed_saved_second_slide_is_served_to_homepage_content(): void
    {
        SiteSetting::query()->create([
            'key' => 'site',
            'value' => [
                'homepage' => [
                    'hero_carousel' => [
                        'slides' => [
                            [
                                'headline' => 'First slide',
                            ],
                            [
                                'headline' => 'Original Business Cards, designed to be remembered',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_08_26_000003_update_classic_business_card_carousel_headline.php',
        );
        $migration->up();

        app(HardcodedContent::class)->forget();

        $this->assertSame(
            'Classic Business Cards, designed to be remembered',
            app(HardcodedContent::class)->section('home_page.hero_carousel.slides.1.headline'),
        );
    }
}
